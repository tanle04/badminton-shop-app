package com.example.badmintonshop.SQLLite;

import android.content.ContentValues;
import android.content.Context;
import android.database.sqlite.SQLiteDatabase;
import android.database.sqlite.SQLiteOpenHelper;

public class BadmintonDb extends SQLiteOpenHelper {
    public static final String DB_NAME = "badminton_shop.db";
    // bump version khi đổi schema
    public static final int DB_VERSION = 2;

    // users
    public static final String TBL_USERS = "users";
    public static final String COL_ID = "id";
    public static final String COL_EMAIL = "email";
    public static final String COL_PASSWORD = "password";

    private static BadmintonDb INSTANCE;
    public static synchronized BadmintonDb getInstance(Context ctx){
        if (INSTANCE == null) INSTANCE = new BadmintonDb(ctx.getApplicationContext());
        return INSTANCE;
    }

    public BadmintonDb(Context ctx) { super(ctx, DB_NAME, null, DB_VERSION); }

    @Override public void onCreate(SQLiteDatabase db) {
        // === USERS ===
        db.execSQL("CREATE TABLE IF NOT EXISTS " + TBL_USERS + " (" +
                COL_ID + " INTEGER PRIMARY KEY AUTOINCREMENT," +
                COL_EMAIL + " TEXT UNIQUE NOT NULL," +
                COL_PASSWORD + " TEXT NOT NULL)");

        // === PRODUCTS ===
        db.execSQL("CREATE TABLE IF NOT EXISTS products(" +
                " productID INTEGER PRIMARY KEY AUTOINCREMENT," +
                " productName TEXT NOT NULL," +
                " price REAL NOT NULL," +
                " stock INTEGER NOT NULL DEFAULT 10," +
                " createdDate TEXT DEFAULT CURRENT_TIMESTAMP)");

        db.execSQL("CREATE TABLE IF NOT EXISTS productimages(" +
                " imageID INTEGER PRIMARY KEY AUTOINCREMENT," +
                " productID INTEGER," +
                " imageUrl TEXT NOT NULL," +
                " FOREIGN KEY(productID) REFERENCES products(productID) ON DELETE CASCADE)");

        db.execSQL("CREATE TABLE IF NOT EXISTS orders(" +
                " orderID INTEGER PRIMARY KEY AUTOINCREMENT," +
                " customerID INTEGER," +
                " orderDate TEXT DEFAULT CURRENT_TIMESTAMP," +
                " status TEXT DEFAULT 'Pending')");

        db.execSQL("CREATE TABLE IF NOT EXISTS orderdetails(" +
                " orderDetailID INTEGER PRIMARY KEY AUTOINCREMENT," +
                " orderID INTEGER," +
                " productID INTEGER," +
                " quantity INTEGER," +
                " price REAL," +
                " FOREIGN KEY(orderID) REFERENCES orders(orderID)," +
                " FOREIGN KEY(productID) REFERENCES products(productID))");

        db.execSQL("CREATE TABLE IF NOT EXISTS promotions(" +
                " promoID INTEGER PRIMARY KEY AUTOINCREMENT," +
                " promoName TEXT," +
                " startDate TEXT," +
                " endDate TEXT," +
                " discountRate INTEGER)");

        db.execSQL("CREATE TABLE IF NOT EXISTS promotionproducts(" +
                " ppID INTEGER PRIMARY KEY AUTOINCREMENT," +
                " productID INTEGER," +
                " promoID INTEGER," +
                " FOREIGN KEY(productID) REFERENCES products(productID)," +
                " FOREIGN KEY(promoID) REFERENCES promotions(promoID))");

        // --- Seed test ---
        ContentValues u = new ContentValues();
        u.put(COL_EMAIL, "test@shop.com");
        u.put(COL_PASSWORD, "123456");
        db.insert(TBL_USERS, null, u);

        db.execSQL("INSERT INTO products(productName,price,stock) VALUES " +
                "('Yonex Astrox 99 Pro',3000000,5)," +
                "('Victor SH-A920',1500000,0)," +
                "('Lining Jersey Set',350000,20)");
        db.execSQL("INSERT INTO productimages(productID,imageUrl) VALUES " +
                "(1,'https://example.com/img/astrox99.jpg')," +
                "(2,'https://example.com/img/a920.jpg')," +
                "(3,'https://example.com/img/lining-set.jpg')");
        db.execSQL("INSERT INTO orders(status) VALUES ('Done')");
        db.execSQL("INSERT INTO orderdetails(orderID,productID,quantity,price) VALUES " +
                "(1,1,3,3000000),(1,3,1,350000)");
        db.execSQL("INSERT INTO promotions(promoName,startDate,endDate,discountRate) VALUES " +
                "('Flash Sale', date('now','-1 day'), date('now','+7 day'), 20)");
        db.execSQL("INSERT INTO promotionproducts(productID,promoID) VALUES (1,1)");
    }

    @Override public void onUpgrade(SQLiteDatabase db, int oldV, int newV) {
        // dev mode: drop & recreate. Khi lên prod thì viết migration cẩn thận.
        db.execSQL("DROP TABLE IF EXISTS promotionproducts");
        db.execSQL("DROP TABLE IF EXISTS promotions");
        db.execSQL("DROP TABLE IF EXISTS orderdetails");
        db.execSQL("DROP TABLE IF EXISTS orders");
        db.execSQL("DROP TABLE IF EXISTS productimages");
        db.execSQL("DROP TABLE IF EXISTS products");
        db.execSQL("DROP TABLE IF EXISTS " + TBL_USERS);
        onCreate(db);
    }

    // === tiện ích login/register (test) ===
    public boolean register(String email, String password){
        ContentValues v = new ContentValues();
        v.put(COL_EMAIL, email.trim());
        v.put(COL_PASSWORD, password);
        try { return getWritableDatabase().insertOrThrow(TBL_USERS, null, v) > 0; }
        catch (Exception e){ return false; }
    }
    public boolean login(String email, String password){
        var c = getReadableDatabase().rawQuery(
                "SELECT 1 FROM "+TBL_USERS+" WHERE "+COL_EMAIL+"=? AND "+COL_PASSWORD+"=? LIMIT 1",
                new String[]{email.trim(), password});
        boolean ok = c.moveToFirst(); c.close(); return ok;
    }
    // Thêm vào BadmintonDb.java
    public boolean emailExists(String email){
        String e = (email == null) ? "" : email.trim();
        android.database.Cursor c = getReadableDatabase().rawQuery(
                "SELECT 1 FROM " + TBL_USERS + " WHERE " + COL_EMAIL + "=? LIMIT 1",
                new String[]{ e }
        );
        boolean exists = c.moveToFirst();
        c.close();
        return exists;
    }

}
