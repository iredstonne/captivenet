CREATE TABLE IF NOT EXISTS sessions (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    device_id INT NOT NULL,
    coupon_id INT NOT NULL,
    started_at TIMESTAMP NOT NULL,
    ended_at TIMESTAMP DEFAULT NULL,
    FOREIGN KEY (device_id) REFERENCES devices(id),
    FOREIGN KEY (coupon_id) REFERENCES coupons(id)
);
