package com.example.badmintonshop.adapter;

import android.content.Context;
import android.graphics.Color;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.RadioButton;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.RecyclerView;
import com.example.badmintonshop.R;
import com.example.badmintonshop.network.dto.VoucherDto;
import java.util.List;
import java.util.Locale;

public class VoucherAdapter extends RecyclerView.Adapter<VoucherAdapter.VoucherViewHolder> {

    private final Context context;
    private final List<VoucherDto> vouchers;
    private final VoucherSelectionListener listener;
    private int selectedPosition = RecyclerView.NO_POSITION;

    public interface VoucherSelectionListener {
        void onVoucherSelected(VoucherDto voucher);
    }

    public VoucherAdapter(Context context, List<VoucherDto> vouchers, int initialSelectedVoucherId, VoucherSelectionListener listener) {
        this.context = context;
        this.vouchers = vouchers;
        this.listener = listener;

        // Tìm vị trí của voucher đã được chọn ban đầu
        for (int i = 0; i < vouchers.size(); i++) {
            if (vouchers.get(i).getVoucherID() == initialSelectedVoucherId) {
                selectedPosition = i;
                break;
            }
        }

        // Cần truyền null nếu không có voucher nào được chọn ban đầu
        if (selectedPosition == RecyclerView.NO_POSITION) {
            listener.onVoucherSelected(null);
        } else {
            listener.onVoucherSelected(vouchers.get(selectedPosition));
        }
    }

    @NonNull
    @Override
    public VoucherViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_voucher, parent, false);
        return new VoucherViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull VoucherViewHolder holder, int position) {
        VoucherDto voucher = vouchers.get(position);

        holder.tvVoucherCode.setText(voucher.getVoucherCode());
        // Sử dụng getDisplayDescription() đã có trong DTO
        holder.tvVoucherDescription.setText(voucher.getDisplayDescription());
        // Sử dụng getMinOrderCondition() đã có trong DTO
        holder.tvMinOrderValue.setText(voucher.getMinOrderCondition());

        // ⭐ HIỂN THỊ TRẠNG THÁI SỬ DỤNG VÀ LOẠI VOUCHER
        double usagePercent = voucher.getUsageLimitPercent();
// Sửa lỗi: dùng isActive() thay vì getIsActive()
        boolean isAvailable = voucher.isActive() && usagePercent > 0;

// Cập nhật text hiển thị giới hạn sử dụng
// Sửa lỗi: dùng isPrivate() thay vì getIsPrivate()
        if (voucher.isPrivate()) {
            holder.tvUsageLimit.setText("Voucher Cá nhân");
            holder.tvUsageLimit.setTextColor(ContextCompat.getColor(context, R.color.green_700));
        } else if (usagePercent > 0 && usagePercent < 100) {
            // Voucher chung và sắp hết
            int usedPercent = 100 - (int) usagePercent;
            holder.tvUsageLimit.setText(String.format(Locale.getDefault(), "Đã dùng hết %d%%", usedPercent));
            holder.tvUsageLimit.setTextColor(ContextCompat.getColor(context, R.color.orange_800));
        } else if (usagePercent == 100) {
            holder.tvUsageLimit.setText("Còn nhiều lượt sử dụng");
            holder.tvUsageLimit.setTextColor(ContextCompat.getColor(context, R.color.gray_600));
        } else {
            // Hết lượt sử dụng (usagePercent <= 0)
            holder.tvUsageLimit.setText("Hết lượt sử dụng");
            holder.tvUsageLimit.setTextColor(ContextCompat.getColor(context, R.color.red_700));
        }

        // ⭐ ĐIỀU KIỆN CHỌN VÀ HIỂN THỊ
        holder.radioButton.setChecked(position == selectedPosition);
        holder.itemView.setEnabled(isAvailable);
        holder.radioButton.setEnabled(isAvailable);

        // Thay đổi màu nền khi không khả dụng
        if (!isAvailable) {
            holder.itemView.setBackgroundColor(ContextCompat.getColor(context, R.color.gray_200));
        } else {
            holder.itemView.setBackgroundColor(Color.WHITE);
        }

        // --- Xử lý click ---
        holder.itemView.setOnClickListener(v -> {
            if (!isAvailable) return; // Không làm gì nếu voucher không khả dụng

            int previousSelectedPosition = selectedPosition;
            int clickedPosition = holder.getAdapterPosition();

            // Nếu click cùng 1 item, hủy chọn (toggling)
            if (previousSelectedPosition == clickedPosition) {
                selectedPosition = RecyclerView.NO_POSITION;
                listener.onVoucherSelected(null);
                notifyItemChanged(previousSelectedPosition);
            } else {
                // Chọn item mới
                selectedPosition = clickedPosition;
                listener.onVoucherSelected(voucher);

                // Cập nhật lại item cũ và item mới
                if (previousSelectedPosition != RecyclerView.NO_POSITION) {
                    notifyItemChanged(previousSelectedPosition);
                }
                notifyItemChanged(selectedPosition);
            }
        });

        // Đảm bảo radio button cũng kích hoạt sự kiện click của item
        holder.radioButton.setOnClickListener(v -> holder.itemView.performClick());
    }

    @Override
    public int getItemCount() {
        return vouchers.size();
    }

    // Phương thức cần thiết cho VoucherSelectionActivity khi nhấn "Không dùng Voucher"
    public void resetSelection() {
        int oldPosition = selectedPosition;
        selectedPosition = RecyclerView.NO_POSITION;
        if (oldPosition != RecyclerView.NO_POSITION) {
            notifyItemChanged(oldPosition);
        }
    }
    // ⭐ HÀM CẦN THIẾT CHO LOGIC TỰ ĐỘNG CHỌN
    public void setSelectedPosition(int position) {
        // Đảm bảo logic notifyItemChanged được gọi
        if (selectedPosition != RecyclerView.NO_POSITION) {
            notifyItemChanged(selectedPosition);
        }
        this.selectedPosition = position;
        if (position != RecyclerView.NO_POSITION) {
            notifyItemChanged(position);
        }
    }
    // ⭐ THÊM: Phương thức để chọn voucher từ Activity
    public void selectVoucherAndNotify(int position) {
        if (position >= 0 && position < vouchers.size()) {
            int previousSelectedPosition = selectedPosition;

            // Đặt vị trí mới
            selectedPosition = position;

            // Kích hoạt listener và truyền voucher được chọn
            listener.onVoucherSelected(vouchers.get(selectedPosition));

            // Cập nhật UI
            if (previousSelectedPosition != RecyclerView.NO_POSITION) {
                notifyItemChanged(previousSelectedPosition);
            }
            notifyItemChanged(selectedPosition);
        }
    }

    // ⭐ PHƯƠNG THỨC MỚI: Cần cho VoucherSelectionActivity để cập nhật data
    public void updateVoucherList(List<VoucherDto> newVouchers) {
        // Cập nhật data
        // Vẫn cần VoucherSelectionActivity tự quản lý việc tìm lại selectedPosition
        // Dùng notifyDataSetChanged() sau khi gọi hàm này
    }

    public static class VoucherViewHolder extends RecyclerView.ViewHolder {
        RadioButton radioButton;
        TextView tvVoucherCode;
        TextView tvVoucherDescription;
        TextView tvMinOrderValue;
        TextView tvUsageLimit; // ⭐ THÊM: View hiển thị giới hạn sử dụng

        public VoucherViewHolder(@NonNull View itemView) {
            super(itemView);
            radioButton = itemView.findViewById(R.id.radio_select_voucher);
            tvVoucherCode = itemView.findViewById(R.id.tv_voucher_code);
            tvVoucherDescription = itemView.findViewById(R.id.tv_voucher_description);
            tvMinOrderValue = itemView.findViewById(R.id.tv_min_order_value);
            // ⭐ ÁNH XẠ VIEW MỚI
            tvUsageLimit = itemView.findViewById(R.id.tv_usage_limit);

            // Đặt logic click cho radio button để đảm bảo click vào toàn bộ item
            radioButton.setClickable(false); // Ngăn radio button tự xử lý click
        }
    }
}