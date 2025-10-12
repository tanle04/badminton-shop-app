// data/ProductRepo.java
package com.example.badmintonshop.repository;

import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;

import com.example.badmintonshop.model.Product;

import java.util.ArrayList;
import java.util.List;

public class ProductRepo {
    private final SQLiteDatabase db;

    public ProductRepo(SQLiteDatabase db) { this.db = db; }

    // BEST SELLING = sum(quantity) desc
    public List<Product> getNewArrivals() {
        String sql =
                "SELECT p.productID, p.productName, p.price, " +
                        "       p.stock AS stock, " +
                        "       COALESCE(pi.imageUrl,'') AS imageUrl " +
                        "FROM products p " +
                        "LEFT JOIN productimages pi ON pi.productID = p.productID " +
                        "GROUP BY p.productID " +
                        "ORDER BY datetime(p.createdDate) DESC " +
                        "LIMIT 10";

        Cursor c = db.rawQuery(sql, null);
        return map(c);
    }

    public List<Product> getComingSoon() {
        String sql =
                "SELECT p.productID, p.productName, p.price, " +
                        "       p.stock AS stock, " +                              // <— thêm dòng này
                        "       COALESCE(pi.imageUrl,'') AS imageUrl " +
                        "FROM products p " +
                        "LEFT JOIN productimages pi ON pi.productID = p.productID " +
                        "WHERE p.stock = 0 " +
                        "   OR EXISTS(SELECT 1 FROM promotionproducts pp " +
                        "             JOIN promotions pr ON pr.promoID = pp.promoID " +
                        "             WHERE pp.productID = p.productID " +
                        "               AND date(pr.startDate) > date('now')) " +
                        "GROUP BY p.productID " +
                        "LIMIT 10";

        Cursor c = db.rawQuery(sql, null);
        return map(c);
    }

    public List<Product> getBestSelling() {
        String sql =
                "SELECT p.productID, p.productName, p.price, " +
                        "       p.stock AS stock, " +
                        "       COALESCE(pi.imageUrl,'') AS imageUrl, " +
                        "       SUM(od.quantity) AS soldQty " +
                        "FROM orderdetails od " +
                        "JOIN products p ON p.productID = od.productID " +
                        "LEFT JOIN productimages pi ON pi.productID = p.productID " +
                        "GROUP BY p.productID " +
                        "ORDER BY soldQty DESC " +
                        "LIMIT 10";

        Cursor c = db.rawQuery(sql, null);
        return map(c);
    }
    private List<Product> map(Cursor c) {
        List<Product> list = new ArrayList<>();
        try {
            while (c.moveToNext()) {
                Product p = new Product();
                p.productID   = c.getInt(c.getColumnIndexOrThrow("productID"));
                p.productName = c.getString(c.getColumnIndexOrThrow("productName"));
                p.price       = c.getDouble(c.getColumnIndexOrThrow("price"));
                int idxImg    = c.getColumnIndex("imageUrl");
                p.imageUrl    = (idxImg >= 0) ? c.getString(idxImg) : null;
                int idxStock  = c.getColumnIndex("stock");
                p.stock       = (idxStock >= 0) ? c.getInt(idxStock) : 0; // fallback
                list.add(p);
            }
        } finally { c.close(); }
        return list;
    }

}
