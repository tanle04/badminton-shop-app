package com.example.badmintonshop.ui;

import android.app.AlertDialog;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.CheckBox;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.example.badmintonshop.R;
import com.example.badmintonshop.adapter.CartAdapter;
import com.example.badmintonshop.model.CartItem;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.ApiService;
import com.example.badmintonshop.network.dto.ApiResponse;
import com.example.badmintonshop.network.dto.CartResponse;
import com.example.badmintonshop.network.dto.ProductDto;
import com.example.badmintonshop.network.dto.VariantListResponse;
import com.google.android.material.appbar.MaterialToolbar;
import com.google.android.material.bottomsheet.BottomSheetDialog;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.chip.Chip;
import com.google.android.material.chip.ChipGroup;

import java.util.ArrayList;
import java.util.List;
import java.util.Locale;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class CartActivity extends AppCompatActivity implements CartAdapter.CartAdapterListener {

    private static final String TAG = "CartActivity";

    private RecyclerView recyclerView;
    private CartAdapter cartAdapter;
    private ApiService api;

    private MaterialToolbar toolbar;
    private TextView tvTotalPrice, tvEmptyCart;
    private MaterialButton btnCheckout;
    private CheckBox cbSelectAll;

    private List<CartItem> cartItems = new ArrayList<>();

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_cart);

        toolbar = findViewById(R.id.toolbar);
        tvTotalPrice = findViewById(R.id.text_total_price);
        btnCheckout = findViewById(R.id.button_checkout);
        tvEmptyCart = findViewById(R.id.tvEmptyCart);
        cbSelectAll = findViewById(R.id.checkbox_select_all);
        recyclerView = findViewById(R.id.recycler_view_cart);
        api = ApiClient.getApiService();

        setSupportActionBar(toolbar);
        toolbar.setNavigationOnClickListener(v -> finish());

        setupRecyclerView();
        fetchCartDataForCurrentUser();

        // Xử lý sự kiện chọn tất cả
        cbSelectAll.setOnClickListener(v -> {
            boolean isChecked = cbSelectAll.isChecked();
            if (cartItems != null) {
                for (CartItem item : cartItems) {
                    item.setSelected(isChecked);
                }
            }
            cartAdapter.notifyDataSetChanged();
            updateSummary();
        });

        // Xử lý sự kiện cho nút "Thanh toán"
        btnCheckout.setOnClickListener(v -> handleCheckout());
    }

    private int getCurrentCustomerId() {
        SharedPreferences prefs = getSharedPreferences("auth", MODE_PRIVATE);
        return prefs.getInt("customerID", -1);
    }

    private void setupRecyclerView() {
        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        cartAdapter = new CartAdapter(this, cartItems, this);
        recyclerView.setAdapter(cartAdapter);
    }

    private void fetchCartDataForCurrentUser() {
        int customerId = getCurrentCustomerId();
        if (customerId == -1) {
            Toast.makeText(this, "Vui lòng đăng nhập để xem giỏ hàng.", Toast.LENGTH_SHORT).show();
            updateEmptyView();
            return;
        }

        api.getCartItems(customerId).enqueue(new Callback<CartResponse>() {
            @Override
            public void onResponse(Call<CartResponse> call, Response<CartResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    cartItems = response.body().getItems();
                    if (cartItems != null && !cartItems.isEmpty()) {
                        cartAdapter.updateData(cartItems);
                        updateVisibleView();
                    } else {
                        updateEmptyView();
                    }
                } else {
                    Log.e(TAG, "Fetch Cart Failed: " + (response.body() != null ? response.body().getMessage() : "HTTP " + response.code()));
                    updateEmptyView();
                }
            }

            @Override
            public void onFailure(Call<CartResponse> call, Throwable t) {
                Log.e(TAG, "Fetch Cart Connection Error: ", t);
                Toast.makeText(CartActivity.this, "Lỗi tải giỏ hàng: " + t.getMessage(), Toast.LENGTH_SHORT).show();
                updateEmptyView();
            }
        });
    }

    private void updateEmptyView() {
        recyclerView.setVisibility(View.GONE);
        tvEmptyCart.setVisibility(View.VISIBLE);
        cartItems.clear();
        cartAdapter.updateData(new ArrayList<>());
        updateSummary();
    }

    private void updateVisibleView() {
        recyclerView.setVisibility(View.VISIBLE);
        tvEmptyCart.setVisibility(View.GONE);
        updateSummary();
    }

    private void updateSummary() {
        double total = 0.0;
        int checkedItemsCount = 0;
        int totalItemsInCart = (cartItems != null) ? cartItems.size() : 0;

        if (cartItems != null) {
            for (CartItem item : cartItems) {
                if (item.isSelected()) {
                    // Cần kiểm tra null/empty cho getVariantPrice
                    try {
                        total += Double.parseDouble(item.getVariantPrice()) * item.getQuantity();
                    } catch (NumberFormatException e) {
                        Log.e(TAG, "Invalid price format for cart item: " + item.getCartID(), e);
                    }
                    checkedItemsCount++;
                }
            }
        }

        tvTotalPrice.setText(String.format(Locale.GERMAN, "%,.0f đ", total));
        btnCheckout.setText(String.format("Thanh toán (%d)", checkedItemsCount));
        toolbar.setTitle(String.format("Giỏ hàng (%d)", totalItemsInCart));
        // Đặt CheckBox "Chọn tất cả" dựa trên trạng thái hiện tại
        cbSelectAll.setChecked(totalItemsInCart > 0 && checkedItemsCount == totalItemsInCart);
        btnCheckout.setEnabled(checkedItemsCount > 0); // Vô hiệu hóa nút thanh toán nếu không có gì được chọn
    }

    // --- Triển khai các phương thức từ CartAdapterListener ---

    @Override
    public void onUpdateQuantity(int cartId, int newQuantity) {
        // Tắt nút checkout trong khi chờ API response
        btnCheckout.setEnabled(false);

        api.updateCartQuantity(getCurrentCustomerId(), cartId, newQuantity).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                btnCheckout.setEnabled(true); // Bật lại nút

                // ⭐ SỬA: Xử lý lỗi chi tiết từ API
                if (response.isSuccessful() && response.body() != null) {
                    if (response.body().isSuccess()) {
                        fetchCartDataForCurrentUser(); // Thành công
                    } else {
                        // Lỗi Logic (ví dụ: Tồn kho không đủ)
                        String message = response.body().getMessage();
                        Toast.makeText(CartActivity.this, "Cập nhật thất bại: " + message, Toast.LENGTH_LONG).show();
                        // Tải lại giỏ hàng để đồng bộ hóa lại UI với server
                        fetchCartDataForCurrentUser();
                    }
                } else {
                    // Lỗi HTTP
                    String message = response.body() != null ? response.body().getMessage() : "Lỗi kết nối server.";
                    Toast.makeText(CartActivity.this, "Cập nhật thất bại: " + message, Toast.LENGTH_LONG).show();
                    fetchCartDataForCurrentUser();
                }
            }
            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                btnCheckout.setEnabled(true); // Bật lại nút
                Toast.makeText(CartActivity.this, "Lỗi kết nối", Toast.LENGTH_SHORT).show();
                fetchCartDataForCurrentUser();
            }
        });
    }

    @Override
    public void onRemoveItem(int cartId, String productName) {
        new AlertDialog.Builder(this)
                .setTitle("Xóa sản phẩm")
                .setMessage("Bạn có chắc muốn xóa '" + productName + "' khỏi giỏ hàng?")
                // Gọi onUpdateQuantity với số lượng 0 để thực hiện xóa
                .setPositiveButton("Xóa", (dialog, which) -> onUpdateQuantity(cartId, 0))
                .setNegativeButton("Hủy", null)
                .show();
    }

    @Override
    public void onItemSelectedChanged() {
        // Chỉ cần cập nhật Summary khi trạng thái chọn thay đổi
        updateSummary();
    }

    @Override
    public void onItemEditClicked(CartItem item) {
        showEditDialog(item);
    }

    // Hàm hiển thị BottomSheetDialog để chỉnh sửa biến thể và số lượng
    private void showEditDialog(final CartItem currentCartItem) {
        final BottomSheetDialog dialog = new BottomSheetDialog(this);
        View sheetView = getLayoutInflater().inflate(R.layout.dialog_edit_cart_item, null);
        dialog.setContentView(sheetView);

        ImageView imageProduct = sheetView.findViewById(R.id.dialog_image_product);
        TextView nameProduct = sheetView.findViewById(R.id.dialog_text_product_name);
        TextView priceProduct = sheetView.findViewById(R.id.dialog_text_product_price);
        ImageButton btnClose = sheetView.findViewById(R.id.dialog_button_close);
        ChipGroup chipGroup = sheetView.findViewById(R.id.dialog_chip_group_variants);
        ImageButton btnDecrease = sheetView.findViewById(R.id.dialog_button_decrease);
        TextView tvQuantity = sheetView.findViewById(R.id.dialog_text_quantity);
        ImageButton btnIncrease = sheetView.findViewById(R.id.dialog_button_increase);
        MaterialButton btnUpdate = sheetView.findViewById(R.id.dialog_button_update);

        final int[] tempQuantity = {currentCartItem.getQuantity()};
        final ProductDto.VariantDto[] selectedVariantHolder = {null}; // Biến giữ phiên bản đã chọn

        // Runnable để cập nhật trạng thái số lượng và các nút (tránh lặp code)
        Runnable updateQuantityUI = () -> {
            int maxStock = (selectedVariantHolder[0] != null) ? selectedVariantHolder[0].getStock() : Integer.MAX_VALUE;
            int currentQty = tempQuantity[0];

            tvQuantity.setText(String.valueOf(currentQty));

            // Ràng buộc nút Tăng: vô hiệu hóa nếu số lượng hiện tại bằng tồn kho
            btnIncrease.setEnabled(currentQty < maxStock);
            // Ràng buộc nút Giảm: vô hiệu hóa nếu số lượng hiện tại là 1
            btnDecrease.setEnabled(currentQty > 1);

            if (currentQty >= maxStock && maxStock != Integer.MAX_VALUE) {
                Toast.makeText(CartActivity.this, "Đã đạt số lượng tồn kho tối đa (" + maxStock + ")", Toast.LENGTH_SHORT).show();
            }
        };

        nameProduct.setText(currentCartItem.getProductName());
        // Giá ban đầu (của item hiện tại trong giỏ)
        try {
            priceProduct.setText(String.format(Locale.GERMAN, "%,.0f đ", Double.parseDouble(currentCartItem.getVariantPrice())));
        } catch (NumberFormatException e) {
            priceProduct.setText("Giá không hợp lệ");
        }

        Glide.with(this).load("http://10.0.2.2/api/BadmintonShop/images/uploads/" + currentCartItem.getImageUrl()).into(imageProduct);

        // Tải các biến thể của sản phẩm
        api.getProductVariants(currentCartItem.getProductID()).enqueue(new Callback<VariantListResponse>() {
            @Override
            public void onResponse(Call<VariantListResponse> call, Response<VariantListResponse> response) {
                // ⭐ SỬA: Log lỗi chi tiết nếu tải biến thể thất bại
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    chipGroup.removeAllViews();
                    for (ProductDto.VariantDto variant : response.body().getVariants()) {
                        Chip chip = new Chip(CartActivity.this);
                        chip.setText(variant.getAttributes());
                        chip.setTag(variant);
                        chip.setCheckable(true);
                        chip.setCheckedIconVisible(false);
                        chip.setEnabled(variant.getStock() > 0);

                        // Đặt biến thể hiện tại làm mặc định được chọn
                        if (variant.getVariantID() == currentCartItem.getVariantID()) {
                            chip.setChecked(true);
                            selectedVariantHolder[0] = variant;
                        }
                        chipGroup.addView(chip);
                    }
                    // Cập nhật trạng thái nút lần đầu
                    updateQuantityUI.run();
                } else {
                    String msg = response.body() != null ? response.body().getMessage() : "HTTP " + response.code();
                    Log.e(TAG, "Failed to load product variants. Code: " + response.code() + ", Msg: " + msg);
                    Toast.makeText(CartActivity.this, "Lỗi tải danh sách phiên bản: " + msg, Toast.LENGTH_LONG).show();
                    dialog.dismiss(); // Đóng dialog nếu không tải được data
                }
            }
            @Override public void onFailure(Call<VariantListResponse> call, Throwable t) {
                Log.e(TAG, "Variant API connection error: ", t);
                Toast.makeText(CartActivity.this, "Lỗi kết nối khi tải phiên bản sản phẩm", Toast.LENGTH_SHORT).show();
                dialog.dismiss(); // Đóng dialog nếu lỗi kết nối
            }
        });

        // Xử lý khi chọn biến thể khác
        chipGroup.setOnCheckedChangeListener((group, checkedId) -> {
            if(checkedId != View.NO_ID) {
                Chip selectedChip = group.findViewById(checkedId);
                ProductDto.VariantDto selectedVariant = (ProductDto.VariantDto) selectedChip.getTag();
                selectedVariantHolder[0] = selectedVariant;

                // Cập nhật giá theo biến thể mới
                priceProduct.setText(String.format(Locale.GERMAN, "%,.0f đ", selectedVariant.getPrice()));

                // ⭐ Reset số lượng về 1 khi chọn biến thể khác có tồn kho
                if (selectedVariant.getStock() > 0) {
                    tempQuantity[0] = 1;
                } else {
                    tempQuantity[0] = 0; // Không thể mua nếu hết hàng
                }
                updateQuantityUI.run();
            }
        });

        // Xử lý nút Cập nhật
        btnUpdate.setOnClickListener(v -> {
            if (selectedVariantHolder[0] == null) {
                Toast.makeText(this, "Vui lòng chọn một phiên bản", Toast.LENGTH_SHORT).show();
                return;
            }

            int newVariantId = selectedVariantHolder[0].getVariantID();
            int newQuantity = tempQuantity[0];
            int maxStock = selectedVariantHolder[0].getStock();

            // ⭐ Kiểm tra tồn kho lần cuối trước khi gọi API
            if (newQuantity <= 0 || newQuantity > maxStock) {
                Toast.makeText(this, "Số lượng không hợp lệ. Tối đa: " + maxStock, Toast.LENGTH_LONG).show();
                updateQuantityUI.run();
                return;
            }

            // Nếu phiên bản không đổi và số lượng không đổi thì đóng dialog
            if (newVariantId == currentCartItem.getVariantID() && newQuantity == currentCartItem.getQuantity()) {
                dialog.dismiss();
                return;
            }

            // Gọi API thay đổi biến thể và số lượng
            api.changeCartItemVariant(getCurrentCustomerId(), currentCartItem.getCartID(), newVariantId, newQuantity).enqueue(new Callback<ApiResponse>() {
                @Override
                public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                    if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                        Toast.makeText(CartActivity.this, "Cập nhật thành công!", Toast.LENGTH_SHORT).show();
                        dialog.dismiss();
                        fetchCartDataForCurrentUser();
                    } else {
                        String message = response.body() != null ? response.body().getMessage() : "Lỗi server.";
                        Toast.makeText(CartActivity.this, "Cập nhật thất bại: " + message, Toast.LENGTH_LONG).show();
                    }
                }
                @Override public void onFailure(Call<ApiResponse> call, Throwable t) {
                    Toast.makeText(CartActivity.this, "Lỗi kết nối", Toast.LENGTH_SHORT).show();
                }
            });
        });

        btnClose.setOnClickListener(v -> dialog.dismiss());

        // ⭐ Logic cho nút Tăng số lượng (sử dụng Runnable)
        btnIncrease.setOnClickListener(v -> {
            int maxStock = (selectedVariantHolder[0] != null) ? selectedVariantHolder[0].getStock() : Integer.MAX_VALUE;
            if (tempQuantity[0] < maxStock) {
                tempQuantity[0]++;
            }
            updateQuantityUI.run();
        });

        // ⭐ Logic cho nút Giảm số lượng (sử dụng Runnable)
        btnDecrease.setOnClickListener(v -> {
            if (tempQuantity[0] > 1) {
                tempQuantity[0]--;
            } else {
                Toast.makeText(CartActivity.this, "Số lượng tối thiểu là 1", Toast.LENGTH_SHORT).show();
            }
            updateQuantityUI.run();
        });

        dialog.show();
    }

    // Hàm xử lý khi nhấn nút "Thanh toán"
    private void handleCheckout() {
        ArrayList<CartItem> selectedItems = new ArrayList<>();
        // Lọc các sản phẩm đã được chọn
        if (cartItems != null) {
            for (CartItem item : cartItems) {
                if (item.isSelected()) {
                    selectedItems.add(item);
                }
            }
        }

        if (selectedItems.isEmpty()) {
            Toast.makeText(this, "Vui lòng chọn sản phẩm để thanh toán", Toast.LENGTH_SHORT).show();
            return;
        }

        // Chuyển sang CheckoutActivity
        Intent intent = new Intent(this, CheckoutActivity.class);
        // Gửi danh sách sản phẩm đã chọn (CartItem phải là Serializable/Parcelable)
        intent.putExtra("SELECTED_ITEMS", selectedItems);
        startActivity(intent);
    }
}