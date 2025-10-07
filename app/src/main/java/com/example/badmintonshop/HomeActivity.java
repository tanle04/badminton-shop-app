// HomeActivity.java
package com.example.badmintonshop;

import android.os.Bundle;

import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.badmintonshop.model.Product;
import com.example.badmintonshop.repository.ProductRepo;
import com.example.badmintonshop.SQLLite.BadmintonDb;      // <- đổi thành DBHelper nếu bạn dùng tên đó
import com.example.badmintonshop.SQLLite.ProductAdapter;

import java.util.List;

public class HomeActivity extends AppCompatActivity {

    private ProductRepo repo;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_home);

        // 1) Mở DB 1 lần – đảm bảo onCreate() chạy để tạo bảng + seed
        BadmintonDb db = BadmintonDb.getInstance(this);   // <- nếu bạn dùng DBHelper: new DBHelper(this)
        db.getWritableDatabase();                         // trigger onCreate() lần đầu

        // 2) Repo dùng Readable DB để query list
        repo = new ProductRepo(db.getReadableDatabase());

        // 3) Bind dữ liệu vào các RecyclerView (nằm ngang)
        bindHorizontalList(R.id.recyclerComingSoon,  repo.getComingSoon());
        bindHorizontalList(R.id.recyclerBestSelling, repo.getBestSelling());
        bindHorizontalList(R.id.recyclerNewArrivals, repo.getNewArrivals());

        // Nếu bạn có thêm Featured:
        // bindHorizontalList(R.id.recyclerFeatured, repo.getFeatured());
    }

    private void bindHorizontalList(int recyclerId, List<Product> data) {
        RecyclerView rv = findViewById(recyclerId);
        if (rv == null) return; // layout chưa có recycler này thì bỏ qua
        rv.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        rv.setAdapter(new ProductAdapter(data));
    }
}
