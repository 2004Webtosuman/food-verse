-- Migration to add structured location columns to users and orders
USE food_verse;

ALTER TABLE users 
ADD COLUMN province VARCHAR(100),
ADD COLUMN district VARCHAR(100),
ADD COLUMN municipality VARCHAR(100);

ALTER TABLE orders 
ADD COLUMN delivery_province VARCHAR(100),
ADD COLUMN delivery_district VARCHAR(100),
ADD COLUMN delivery_municipality VARCHAR(100);
