package com.example.badmintonshop.adapter;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.RadioButton;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.badmintonshop.R;
import com.example.badmintonshop.network.dto.AddressDto;
import java.util.List;

public class AddressAdapter extends RecyclerView.Adapter<AddressAdapter.ViewHolder> {

    public interface AddressAdapterListener {
        void onEditClicked(AddressDto address);
        void onDeleteClicked(AddressDto address);
        void onSetDefaultClicked(AddressDto address);
        void onAddressSelected(AddressDto address); // 🚩 THÊM: Sự kiện khi chọn một địa chỉ
    }

    private List<AddressDto> addressList;
    private final Context context;
    private final AddressAdapterListener listener;

    public AddressAdapter(Context context, List<AddressDto> addressList, AddressAdapterListener listener) {
        this.context = context;
        this.addressList = addressList;
        this.listener = listener;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_address, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        AddressDto address = addressList.get(position);

        holder.recipientName.setText(address.getRecipientName());
        holder.phone.setText(address.getPhone());
        String fullAddress = address.getStreet() + ", " + address.getCity() + ", " + address.getCountry();
        holder.fullAddress.setText(fullAddress);
        holder.defaultTag.setVisibility(address.isDefault() ? View.VISIBLE : View.GONE);
        holder.rbSetDefault.setChecked(address.isDefault());

        // --- Thiết lập sự kiện click ---
        holder.btnEdit.setOnClickListener(v -> listener.onEditClicked(address));
        holder.btnDelete.setOnClickListener(v -> listener.onDeleteClicked(address));

        holder.rbSetDefault.setOnClickListener(v -> {
            if (!address.isDefault()) {
                listener.onSetDefaultClicked(address);
            }
        });

        // 🚩 THÊM: Sự kiện click vào toàn bộ item để chọn địa chỉ
        holder.addressContent.setOnClickListener(v -> listener.onAddressSelected(address));
    }

    @Override
    public int getItemCount() {
        return addressList != null ? addressList.size() : 0;
    }

    public void updateData(List<AddressDto> newAddresses) {
        this.addressList = newAddresses;
        notifyDataSetChanged();
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView recipientName, phone, fullAddress, defaultTag;
        TextView btnEdit, btnDelete;
        RadioButton rbSetDefault;
        View addressContent; // 🚩 THÊM: Tham chiếu đến vùng chứa nội dung

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            recipientName = itemView.findViewById(R.id.tv_recipient_name);
            phone = itemView.findViewById(R.id.tv_phone);
            fullAddress = itemView.findViewById(R.id.tv_full_address);
            defaultTag = itemView.findViewById(R.id.tv_default_tag);
            btnEdit = itemView.findViewById(R.id.btn_edit);
            btnDelete = itemView.findViewById(R.id.btn_delete);
            rbSetDefault = itemView.findViewById(R.id.rb_set_default);
            addressContent = itemView.findViewById(R.id.address_content_layout); // 🚩 Ánh xạ vùng nội dung
        }
    }
}