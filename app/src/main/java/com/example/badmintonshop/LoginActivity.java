package com.example.badmintonshop;

import android.content.Intent;
import android.os.Bundle;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.badmintonshop.SQLLite.BadmintonDb;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

public class LoginActivity extends AppCompatActivity {

    // LoginActivity.java (chỉ phần logic thêm)
    BadmintonDb db;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);
        db = new BadmintonDb(this);

        MaterialButton btnLogin = findViewById(R.id.btnLogin);
        TextInputEditText etEmail = findViewById(R.id.etEmail);
        TextInputEditText etPassword = findViewById(R.id.etPassword);
        TextView tvRegister = findViewById(R.id.tvRegister);

        btnLogin.setOnClickListener(v -> {
            String email = String.valueOf(etEmail.getText());
            String pass  = String.valueOf(etPassword.getText());

            if (email.isEmpty() || pass.isEmpty()) {
                Toast.makeText(this, "Nhập email và mật khẩu", Toast.LENGTH_SHORT).show();
                return;
            }
            if (db.login(email, pass)) {
                Toast.makeText(this, "Đăng nhập thành công!", Toast.LENGTH_SHORT).show();
                startActivity(new Intent(this, HomeActivity.class));
                finish();
            } else {
                Toast.makeText(this, "Sai thông tin đăng nhập", Toast.LENGTH_SHORT).show();
            }
        });

        tvRegister.setOnClickListener(v ->
                startActivity(new Intent(this, RegisterActivity.class))
        );
    }

}