package com.example.badmintonshop.adapter;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.example.badmintonshop.R;
import com.example.badmintonshop.network.ApiClient;
import com.example.badmintonshop.network.dto.EmployeesListResponse;

import java.util.List;

public class EmployeeSelectionAdapter extends RecyclerView.Adapter<EmployeeSelectionAdapter.ViewHolder> {

    private Context context;
    private List<EmployeesListResponse.Employee> employees;
    private OnEmployeeSelectedListener listener;

    public interface OnEmployeeSelectedListener {
        void onEmployeeSelected(EmployeesListResponse.Employee employee);
    }

    public EmployeeSelectionAdapter(Context context, List<EmployeesListResponse.Employee> employees, OnEmployeeSelectedListener listener) {
        this.context = context;
        this.employees = employees;
        this.listener = listener;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_employee_selection, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        EmployeesListResponse.Employee employee = employees.get(position);

        holder.textViewName.setText(employee.getFullName());
        holder.textViewRole.setText(employee.getRole());

        // Load avatar
        if (employee.getImgUrl() != null && !employee.getImgUrl().isEmpty()) {
            String imageUrl = ApiClient.BASE_STORAGE_URL + employee.getImgUrl();
            Glide.with(context)
                    .load(imageUrl)
                    .placeholder(R.drawable.ic_employee_default)
                    .error(R.drawable.ic_employee_default)
                    .circleCrop()
                    .into(holder.imageViewAvatar);
        } else {
            holder.imageViewAvatar.setImageResource(R.drawable.ic_employee_default);
        }

        // Online status
        holder.viewOnlineStatus.setVisibility(employee.isOnline() ? View.VISIBLE : View.GONE);

        holder.itemView.setOnClickListener(v -> {
            if (listener != null) {
                listener.onEmployeeSelected(employee);
            }
        });
    }

    @Override
    public int getItemCount() {
        return employees.size();
    }

    static class ViewHolder extends RecyclerView.ViewHolder {
        ImageView imageViewAvatar;
        TextView textViewName;
        TextView textViewRole;
        View viewOnlineStatus;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            imageViewAvatar = itemView.findViewById(R.id.imageViewAvatar);
            textViewName = itemView.findViewById(R.id.textViewName);
            textViewRole = itemView.findViewById(R.id.textViewRole);
            viewOnlineStatus = itemView.findViewById(R.id.viewOnlineStatus);
        }
    }
}