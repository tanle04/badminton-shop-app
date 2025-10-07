package com.example.badmintonshop;

import android.os.Bundle;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;

import com.example.badmintonshop.SQLLite.BadmintonDb;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

public class RegisterActivity extends AppCompatActivity {
    BadmintonDb db;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_register); // bạn đã có layout

        db = new BadmintonDb(this);
        TextInputEditText etEmail = findViewById(R.id.etEmailRegister);
        TextInputEditText etPassword = findViewById(R.id.etPasswordRegister);
        TextInputEditText etPassword2 = findViewById(R.id.etConfirmPassword);
        MaterialButton btnRegister = findViewById(R.id.btnRegister);

        btnRegister.setOnClickListener(v -> {
            String email = String.valueOf(etEmail.getText());
            String p1 = String.valueOf(etPassword.getText());
            String p2 = String.valueOf(etPassword2.getText());

            if (email.isEmpty() || p1.isEmpty() || p2.isEmpty()) {
                Toast.makeText(this, "Điền đủ thông tin", Toast.LENGTH_SHORT).show();
                return;
            }
            if (!p1.equals(p2)) {
                Toast.makeText(this, "Mật khẩu không khớp", Toast.LENGTH_SHORT).show();
                return;
            }
            if (db.emailExists(email)) {
                Toast.makeText(this, "Email đã tồn tại", Toast.LENGTH_SHORT).show();
                return;
            }
            if (db.register(email, p1)) {
                Toast.makeText(this, "Đăng ký thành công", Toast.LENGTH_SHORT).show();
                finish(); // quay lại Login
            } else {
                Toast.makeText(this, "Đăng ký thất bại", Toast.LENGTH_SHORT).show();
            }
        });
    }
}
