package com.example.badmintonshop.adapter;

import android.content.Context;
import android.content.Intent;
import android.net.Uri;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.bumptech.glide.Glide;
import com.example.badmintonshop.R;
import com.example.badmintonshop.network.dto.SliderDto;
import java.util.List;

public class BannerAdapter extends RecyclerView.Adapter<BannerAdapter.ViewHolder> {
    private final Context ctx;
    private final List<SliderDto> items;

    public BannerAdapter(Context ctx, List<SliderDto> items) {
        this.ctx = ctx;
        this.items = items;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View v = LayoutInflater.from(ctx).inflate(R.layout.item_banner, parent, false);
        return new ViewHolder(v);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder h, int pos) {
        SliderDto s = items.get(pos);
        h.tvTitle.setText(s.getTitle());

        Glide.with(ctx)
                .load(s.getImageUrl())
                .placeholder(R.drawable.ic_badminton_logo1)
                .error(R.drawable.ic_badminton_logo)
                .into(h.img);

        h.itemView.setOnClickListener(v -> {
            if (s.getBacklink() != null && !s.getBacklink().isEmpty()) {
                Intent i = new Intent(Intent.ACTION_VIEW, Uri.parse(s.getBacklink()));
                ctx.startActivity(i);
            }
        });
    }

    @Override
    public int getItemCount() {
        return items.size();
    }

    static class ViewHolder extends RecyclerView.ViewHolder {
        ImageView img;
        TextView tvTitle;

        ViewHolder(View v) {
            super(v);
            img = v.findViewById(R.id.imgBanner);
            tvTitle = v.findViewById(R.id.tvBannerTitle);
        }
    }
}
