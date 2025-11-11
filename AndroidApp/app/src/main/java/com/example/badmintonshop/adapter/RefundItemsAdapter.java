package com.example.badmintonshop.adapter;

import android.content.Context;
import android.text.Editable;
import android.text.TextWatcher;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.CheckBox;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.Spinner;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.example.badmintonshop.R;
import com.example.badmintonshop.network.dto.OrderDetailDto;
import com.example.badmintonshop.network.dto.RefundRequestBody;

import java.util.ArrayList;
import java.util.List;

public class RefundItemsAdapter extends RecyclerView.Adapter<RefundItemsAdapter.ItemViewHolder> {

    private final Context context;
    private final List<OrderDetailDto> items;
    private final List<ItemState> itemStates; // Nơi lưu trữ trạng thái thực tế

    // ⭐ Lớp nội bộ đã được nâng cấp để lưu trữ mọi thứ
    private static class ItemState {
        int orderDetailID;
        boolean isSelected = false;
        int selectedQuantity = 1; // Mặc định số lượng là 1
        String itemReason = "";

        ItemState(int orderDetailID) {
            this.orderDetailID = orderDetailID;
        }
    }

    public RefundItemsAdapter(Context context, List<OrderDetailDto> items) {
        this.context = context;
        this.items = items;
        this.itemStates = new ArrayList<>();
        // Khởi tạo danh sách trạng thái
        for (OrderDetailDto item : items) {
            this.itemStates.add(new ItemState(item.getOrderDetailID()));
        }
    }

    @NonNull
    @Override
    public ItemViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_refund_product, parent, false);
        return new ItemViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ItemViewHolder holder, int position) {
        OrderDetailDto item = items.get(position);
        ItemState state = itemStates.get(position);

        // Cờ này ngăn các listener chạy khi chúng ta đang bind dữ liệu
        holder.isBinding = true;

        // 1. Cài đặt dữ liệu tĩnh
        holder.cbSelectItem.setText(item.getProductName());
        holder.tvVariantDetails.setText("Phân loại: " + item.getVariantDetails());
        holder.tvQuantityOrdered.setText("Số lượng mua: " + item.getQuantity());

        Glide.with(context)
                .load(item.getImageUrl())
                .placeholder(R.drawable.placeholder_image)
                .into(holder.imgProduct);

        // 2. Cài đặt Spinner (phải làm ở đây vì số lượng mỗi item khác nhau)
        List<Integer> quantities = new ArrayList<>();
        for (int i = 1; i <= item.getQuantity(); i++) {
            quantities.add(i);
        }
        ArrayAdapter<Integer> spinnerAdapter = new ArrayAdapter<>(context, android.R.layout.simple_spinner_item, quantities);
        spinnerAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        holder.spinnerQuantity.setAdapter(spinnerAdapter);

        // 3. Khôi phục trạng thái từ ItemState
        holder.cbSelectItem.setChecked(state.isSelected);

        // Gỡ listener cũ, set text, gán lại listener
        holder.etItemReason.removeTextChangedListener(holder.textWatcher);
        holder.etItemReason.setText(state.itemReason);
        holder.etItemReason.addTextChangedListener(holder.textWatcher);

        // Đặt đúng giá trị cho spinner (ví dụ: số 1 = index 0)
        int spinnerPosition = state.selectedQuantity - 1;
        if (spinnerPosition >= 0 && spinnerPosition < spinnerAdapter.getCount()) {
            holder.spinnerQuantity.setSelection(spinnerPosition);
        }

        // 4. Hiển thị/ẩn các trường
        holder.etItemReason.setVisibility(state.isSelected ? View.VISIBLE : View.GONE);
        holder.spinnerQuantity.setVisibility(state.isSelected ? View.VISIBLE : View.GONE);

        // Đã bind xong, bật lại listener
        holder.isBinding = false;
    }

    @Override
    public int getItemCount() {
        return items.size();
    }

    /**
     * ⭐ Hàm này giờ rất an toàn: chỉ đọc từ danh sách ItemState,
     * không cần quan tâm đến View.
     */
    public List<RefundRequestBody.RefundItem> getSelectedRefundItems() {
        List<RefundRequestBody.RefundItem> selectedItems = new ArrayList<>();
        for (ItemState state : itemStates) {
            if (state.isSelected) {
                selectedItems.add(new RefundRequestBody.RefundItem(
                        state.orderDetailID,
                        state.selectedQuantity,
                        state.itemReason
                ));
            }
        }
        return selectedItems;
    }


    /**
     * ⭐ ViewHolder được viết lại hoàn toàn:
     * 1. Xóa code lỗi khỏi constructor.
     * 2. Thêm listener (chỉ 1 lần) để cập nhật ItemState khi user tương tác.
     */
    class ItemViewHolder extends RecyclerView.ViewHolder {
        CheckBox cbSelectItem;
        ImageView imgProduct;
        TextView tvVariantDetails, tvQuantityOrdered;
        Spinner spinnerQuantity;
        EditText etItemReason;

        boolean isBinding = false; // Cờ để tránh listener chạy khi đang bind
        TextWatcher textWatcher; // Lưu lại để có thể gỡ ra

        public ItemViewHolder(@NonNull View itemView) {
            super(itemView);
            // Tìm Views
            cbSelectItem = itemView.findViewById(R.id.cb_select_item);
            imgProduct = itemView.findViewById(R.id.img_product);
            tvVariantDetails = itemView.findViewById(R.id.tv_variant_details);
            tvQuantityOrdered = itemView.findViewById(R.id.tv_quantity_ordered);
            spinnerQuantity = itemView.findViewById(R.id.spinner_refund_quantity);
            etItemReason = itemView.findViewById(R.id.et_item_reason);

            // --- GÁN LISTENER MỘT LẦN DUY NHẤT ---

            // 1. CheckBox Listener
            cbSelectItem.setOnCheckedChangeListener((buttonView, isChecked) -> {
                if (isBinding) return; // Không làm gì nếu đang bind
                int pos = getAdapterPosition();
                if (pos != RecyclerView.NO_POSITION) {
                    itemStates.get(pos).isSelected = isChecked;
                    // Cập nhật UI trực tiếp
                    etItemReason.setVisibility(isChecked ? View.VISIBLE : View.GONE);
                    spinnerQuantity.setVisibility(isChecked ? View.VISIBLE : View.GONE);
                }
            });

            // 2. Spinner Listener
            spinnerQuantity.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
                @Override
                public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {
                    if (isBinding) return;
                    int pos = getAdapterPosition();
                    if (pos != RecyclerView.NO_POSITION) {
                        int quantity = (Integer) parent.getItemAtPosition(position);
                        itemStates.get(pos).selectedQuantity = quantity;
                    }
                }
                @Override
                public void onNothingSelected(AdapterView<?> parent) {}
            });

            // 3. EditText Listener
            textWatcher = new TextWatcher() {
                @Override public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
                @Override public void onTextChanged(CharSequence s, int start, int before, int count) {}
                @Override
                public void afterTextChanged(Editable s) {
                    if (isBinding) return;
                    int pos = getAdapterPosition();
                    if (pos != RecyclerView.NO_POSITION) {
                        itemStates.get(pos).itemReason = s.toString();
                    }
                }
            };
            etItemReason.addTextChangedListener(textWatcher);
        }
    }
}