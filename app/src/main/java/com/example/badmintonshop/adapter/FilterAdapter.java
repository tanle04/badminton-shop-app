package com.example.badmintonshop.adapter;

import android.content.Context;
import android.graphics.Color;
import android.graphics.Typeface;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.R;
import com.example.badmintonshop.model.FilterHeader;
import com.example.badmintonshop.model.FilterItem;
import com.example.badmintonshop.model.FilterOption;

import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.HashMap;

public class FilterAdapter extends RecyclerView.Adapter<RecyclerView.ViewHolder> {

    // Interface để giao tiếp với Activity
    public interface OnFilterClickListener {
        void onFilterChanged();
    }

    private final List<FilterItem> masterList; // Danh sách đầy đủ chứa cả header và option
    private final List<FilterItem> displayList; // Danh sách chỉ chứa các item được hiển thị
    private final OnFilterClickListener listener;

    public FilterAdapter(List<FilterItem> items, OnFilterClickListener listener) {
        this.masterList = items;
        this.displayList = new ArrayList<>();
        this.listener = listener;
        updateDisplayList(); // Cập nhật danh sách hiển thị lần đầu
    }

    // --- ViewHolder cho Tiêu đề ---
    private static class HeaderViewHolder extends RecyclerView.ViewHolder {
        TextView tvHeaderTitle;
        HeaderViewHolder(@NonNull View itemView) {
            super(itemView);
            tvHeaderTitle = itemView.findViewById(R.id.tvHeaderTitle);
        }
    }

    // --- ViewHolder cho Tùy chọn ---
    private static class OptionViewHolder extends RecyclerView.ViewHolder {
        TextView tvOptionName;
        OptionViewHolder(@NonNull View itemView) {
            super(itemView);
            tvOptionName = itemView.findViewById(R.id.tvOptionName);
        }
    }

    @Override
    public int getItemViewType(int position) {
        return displayList.get(position).getType();
    }

    @NonNull
    @Override
    public RecyclerView.ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        LayoutInflater inflater = LayoutInflater.from(parent.getContext());
        if (viewType == FilterItem.TYPE_HEADER) {
            View view = inflater.inflate(R.layout.item_filter_header, parent, false);
            return new HeaderViewHolder(view);
        } else { // TYPE_OPTION
            View view = inflater.inflate(R.layout.item_filter_option, parent, false);
            return new OptionViewHolder(view);
        }
    }

    @Override
    public void onBindViewHolder(@NonNull RecyclerView.ViewHolder holder, int position) {
        FilterItem item = displayList.get(position);

        if (holder.getItemViewType() == FilterItem.TYPE_HEADER) {
            HeaderViewHolder headerHolder = (HeaderViewHolder) holder;
            FilterHeader header = (FilterHeader) item;
            headerHolder.tvHeaderTitle.setText(header.name);

            headerHolder.itemView.setOnClickListener(v -> {
                header.isExpanded = !header.isExpanded; // Đảo ngược trạng thái mở/đóng
                updateDisplayList(); // Cập nhật lại danh sách hiển thị
            });

        } else { // TYPE_OPTION
            OptionViewHolder optionHolder = (OptionViewHolder) holder;
            FilterOption option = (FilterOption) item;
            optionHolder.tvOptionName.setText(option.name);

            // Thay đổi giao diện nếu mục được chọn
            if (option.isSelected) {
                optionHolder.tvOptionName.setTypeface(null, Typeface.BOLD);
                optionHolder.tvOptionName.setTextColor(Color.parseColor("#FF6700")); // Màu cam
            } else {
                optionHolder.tvOptionName.setTypeface(null, Typeface.NORMAL);
                optionHolder.tvOptionName.setTextColor(Color.BLACK);
            }

            optionHolder.itemView.setOnClickListener(v -> {
                // Bỏ chọn tất cả các mục khác trong cùng một nhóm
                unselectAllOptionsInGroup(option);
                // Chọn mục hiện tại
                option.isSelected = !option.isSelected;
                updateDisplayList(); // Cập nhật lại giao diện
                listener.onFilterChanged(); // Báo cho Activity biết để tải lại sản phẩm
            });
        }
    }

    @Override
    public int getItemCount() {
        return displayList.size();
    }

    // Hàm then chốt: Cập nhật lại danh sách sẽ được hiển thị
    private void updateDisplayList() {
        displayList.clear();
        for (FilterItem item : masterList) {
            if (item.getType() == FilterItem.TYPE_HEADER) {
                displayList.add(item);
                FilterHeader header = (FilterHeader) item;
                if (!header.isExpanded) {
                    // Nếu header bị đóng, tìm đến header tiếp theo và bỏ qua các option ở giữa
                    int nextHeaderIndex = findNextHeaderIndex(masterList.indexOf(header));
                    if (nextHeaderIndex != -1) {
                        // Bỏ qua các option bằng cách nhảy index
                        // Do vòng lặp sẽ tự tăng, ta cần trừ 1
                        int masterListIndex = masterList.indexOf(item);
                        // This logic is complex in a for-each loop, let's rebuild with index
                        rebuildWithIndex();
                        return;
                    }
                }
            } else { // TYPE_OPTION
                displayList.add(item);
            }
        }
        notifyDataSetChanged();
    }

    private void rebuildWithIndex(){
        displayList.clear();
        for(int i = 0; i < masterList.size(); i++){
            FilterItem item = masterList.get(i);
            displayList.add(item);
            if(item.getType() == FilterItem.TYPE_HEADER){
                FilterHeader header = (FilterHeader) item;
                if(!header.isExpanded){
                    int nextHeader = findNextHeaderIndex(i);
                    if(nextHeader != -1){
                        i = nextHeader - 1; // Loop will increment to nextHeader
                    } else {
                        // This was the last header, so we are done
                        break;
                    }
                }
            }
        }
        notifyDataSetChanged();
    }


    private int findNextHeaderIndex(int currentIndex) {
        for (int i = currentIndex + 1; i < masterList.size(); i++) {
            if (masterList.get(i).getType() == FilterItem.TYPE_HEADER) {
                return i;
            }
        }
        return -1; // Không tìm thấy header nào khác
    }

    private void unselectAllOptionsInGroup(FilterOption selectedOption) {
        int headerIndex = -1;
        // Tìm header của nhóm chứa option được chọn
        for (int i = masterList.indexOf(selectedOption) - 1; i >= 0; i--) {
            if (masterList.get(i).getType() == FilterItem.TYPE_HEADER) {
                headerIndex = i;
                break;
            }
        }

        if (headerIndex == -1) return;

        // Bỏ chọn tất cả các option trong nhóm đó
        for (int i = headerIndex + 1; i < masterList.size(); i++) {
            FilterItem item = masterList.get(i);
            if (item.getType() == FilterItem.TYPE_OPTION) {
                ((FilterOption) item).isSelected = false;
            } else { // Đã đến header tiếp theo
                break;
            }
        }
    }

    // --- Các hàm để Activity lấy giá trị bộ lọc ---
    public String getSelectedFilterValue(String headerName) {
        int headerIndex = -1;
        for (int i = 0; i < masterList.size(); i++) {
            FilterItem item = masterList.get(i);
            if (item.getType() == FilterItem.TYPE_HEADER && item.name.equals(headerName)) {
                headerIndex = i;
                break;
            }
        }

        if (headerIndex == -1) return null;

        for (int i = headerIndex + 1; i < masterList.size(); i++) {
            FilterItem item = masterList.get(i);
            if (item.getType() == FilterItem.TYPE_OPTION) {
                if (((FilterOption) item).isSelected) {
                    return item.name;
                }
            } else {
                break;
            }
        }
        return null; // Không có mục nào được chọn
    }
}