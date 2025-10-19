package com.example.badmintonshop.adapter;

import androidx.annotation.NonNull;
import androidx.fragment.app.Fragment;
import androidx.fragment.app.FragmentActivity;
import androidx.viewpager2.adapter.FragmentStateAdapter;

import com.example.badmintonshop.ui.OrderFragment;

public class OrderPagerAdapter extends FragmentStateAdapter {

    private final String[] tabTitles;

    public OrderPagerAdapter(@NonNull FragmentActivity fragmentActivity, String[] tabTitles) {
        super(fragmentActivity);
        this.tabTitles = tabTitles;
    }

    @NonNull
    @Override
    public Fragment createFragment(int position) {
        String status = tabTitles[position];
        return OrderFragment.newInstance(status);
    }

    @Override
    public int getItemCount() {
        return tabTitles.length;
    }
}