package com.example.badmintonshop.ui;

import android.app.AlertDialog;
import android.content.SharedPreferences;
import android.os.Bundle;
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

// 🚩 Implement interface của Adapter để nhận sự kiện click
public class CartActivity extends AppCompatActivity implements CartAdapter.CartAdapterListener {

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

        setupRecyclerView();
        fetchCartDataForCurrentUser();

        cbSelectAll.setOnClickListener(v -> {
            boolean isChecked = cbSelectAll.isChecked();
            for (CartItem item : cartItems) {
                item.setSelected(isChecked);
            }
            cartAdapter.notifyDataSetChanged();
            updateSummary();
        });
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
            Toast.makeText(this, "Bạn chưa đăng nhập!", Toast.LENGTH_LONG).show();
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
                    updateEmptyView();
                }
            }

            @Override
            public void onFailure(Call<CartResponse> call, Throwable t) {
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
                    total += Double.parseDouble(item.getVariantPrice()) * item.getQuantity();
                    checkedItemsCount++;
                }
            }
        }

        tvTotalPrice.setText(String.format(Locale.GERMAN, "%,.0f đ", total));
        btnCheckout.setText(String.format("Thanh toán (%d)", checkedItemsCount));
        toolbar.setTitle(String.format("Giỏ hàng (%d)", totalItemsInCart));
        cbSelectAll.setChecked(totalItemsInCart > 0 && checkedItemsCount == totalItemsInCart);
    }

    // --- Triển khai các phương thức từ CartAdapterListener ---

    @Override
    public void onUpdateQuantity(int cartId, int newQuantity) {
        api.updateCartQuantity(getCurrentCustomerId(), cartId, newQuantity).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    fetchCartDataForCurrentUser();
                } else {
                    Toast.makeText(CartActivity.this, "Cập nhật thất bại", Toast.LENGTH_SHORT).show();
                }
            }
            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                Toast.makeText(CartActivity.this, "Lỗi kết nối", Toast.LENGTH_SHORT).show();
            }
        });
    }

    @Override
    public void onRemoveItem(int cartId, String productName) {
        new AlertDialog.Builder(this)
                .setTitle("Xóa sản phẩm")
                .setMessage("Bạn có chắc muốn xóa '" + productName + "' khỏi giỏ hàng?")
                .setPositiveButton("Xóa", (dialog, which) -> onUpdateQuantity(cartId, 0))
                .setNegativeButton("Hủy", null)
                .show();
    }

    @Override
    public void onItemSelectedChanged() {
        updateSummary();
    }

    // 🚩 NEW: TRIỂN KHAI HÀM MỞ POPUP CHỈNH SỬA
    @Override
    public void onItemEditClicked(CartItem item) {
        showEditDialog(item);
    }

    private void showEditDialog(final CartItem currentCartItem) {
        final BottomSheetDialog dialog = new BottomSheetDialog(this);
        View sheetView = getLayoutInflater().inflate(R.layout.dialog_edit_cart_item, null);
        dialog.setContentView(sheetView);

        // Ánh xạ view trong dialog
        ImageView imageProduct = sheetView.findViewById(R.id.dialog_image_product);
        TextView nameProduct = sheetView.findViewById(R.id.dialog_text_product_name);
        TextView priceProduct = sheetView.findViewById(R.id.dialog_text_product_price);
        ImageButton btnClose = sheetView.findViewById(R.id.dialog_button_close);
        ChipGroup chipGroup = sheetView.findViewById(R.id.dialog_chip_group_variants);
        ImageButton btnDecrease = sheetView.findViewById(R.id.dialog_button_decrease);
        TextView tvQuantity = sheetView.findViewById(R.id.dialog_text_quantity);
        ImageButton btnIncrease = sheetView.findViewById(R.id.dialog_button_increase);
        MaterialButton btnUpdate = sheetView.findViewById(R.id.dialog_button_update);

        // Biến tạm để lưu trữ lựa chọn trong dialog
        final int[] tempQuantity = {currentCartItem.getQuantity()};

        // Điền dữ liệu ban đầu
        nameProduct.setText(currentCartItem.getProductName());
        priceProduct.setText(String.format(Locale.GERMAN, "%,.0f đ", Double.parseDouble(currentCartItem.getVariantPrice())));
        tvQuantity.setText(String.valueOf(tempQuantity[0]));
        Glide.with(this).load("http://10.0.2.2/api/BadmintonShop/images/uploads/" + currentCartItem.getImageUrl()).into(imageProduct);

        // Tải và hiển thị các phiên bản có sẵn
        api.getProductVariants(currentCartItem.getProductID()).enqueue(new Callback<VariantListResponse>() {
            @Override
            public void onResponse(Call<VariantListResponse> call, Response<VariantListResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    chipGroup.removeAllViews();
                    for (ProductDto.VariantDto variant : response.body().getVariants()) {
                        Chip chip = new Chip(CartActivity.this);
                        chip.setText(variant.getAttributes());
                        chip.setTag(variant); // Lưu cả object variant vào tag
                        chip.setCheckable(true);
                        chip.setCheckedIconVisible(false); // Ẩn icon tick

                        chip.setEnabled(variant.getStock() > 0);
                        if (variant.getVariantID() == currentCartItem.getVariantID()) {
                            chip.setChecked(true);
                        }
                        chipGroup.addView(chip);
                    }
                }
            }
            @Override public void onFailure(Call<VariantListResponse> call, Throwable t) {}
        });

        chipGroup.setOnCheckedChangeListener((group, checkedId) -> {
            if(checkedId != View.NO_ID) {
                Chip selectedChip = group.findViewById(checkedId);
                ProductDto.VariantDto selectedVariant = (ProductDto.VariantDto) selectedChip.getTag();
                priceProduct.setText(String.format(Locale.GERMAN, "%,.0f đ", selectedVariant.getPrice()));
            }
        });

        // Xử lý nút Cập nhật
        btnUpdate.setOnClickListener(v -> {
            int selectedChipId = chipGroup.getCheckedChipId();
            if (selectedChipId == View.NO_ID) {
                Toast.makeText(this, "Vui lòng chọn một phiên bản", Toast.LENGTH_SHORT).show();
                return;
            }

            Chip selectedChip = chipGroup.findViewById(selectedChipId);
            ProductDto.VariantDto selectedVariant = (ProductDto.VariantDto) selectedChip.getTag();
            int newVariantId = selectedVariant.getVariantID();
            int newQuantity = tempQuantity[0];

            api.changeCartItemVariant(getCurrentCustomerId(), currentCartItem.getCartID(), newVariantId, newQuantity).enqueue(new Callback<ApiResponse>() {
                @Override
                public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                    if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                        dialog.dismiss();
                        fetchCartDataForCurrentUser(); // Tải lại toàn bộ giỏ hàng
                    } else {
                        Toast.makeText(CartActivity.this, "Cập nhật thất bại", Toast.LENGTH_SHORT).show();
                    }
                }
                @Override public void onFailure(Call<ApiResponse> call, Throwable t) {
                    Toast.makeText(CartActivity.this, "Lỗi kết nối", Toast.LENGTH_SHORT).show();
                }
            });
        });

        // Listener cho các nút còn lại trong dialog
        btnClose.setOnClickListener(v -> dialog.dismiss());
        btnIncrease.setOnClickListener(v -> {
            tempQuantity[0]++;
            tvQuantity.setText(String.valueOf(tempQuantity[0]));
        });
        btnDecrease.setOnClickListener(v -> {
            if (tempQuantity[0] > 1) {
                tempQuantity[0]--;
                tvQuantity.setText(String.valueOf(tempQuantity[0]));
            }
        });

        dialog.show();
    }
}