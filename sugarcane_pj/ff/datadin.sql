CREATE DATABASE IF NOT EXISTS sugarcane_project;

USE sugarcane_project;

CREATE TABLE IF NOT EXISTS soil_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_year VARCHAR(10) NOT NULL,  -- ปีการผลิต เช่น '68-69'
    agency VARCHAR(255) DEFAULT NULL,
    contract_number VARCHAR(255) DEFAULT NULL,
    quota VARCHAR(255) DEFAULT NULL,
    plot_id VARCHAR(255) DEFAULT NULL,
    rai_area INT DEFAULT NULL,
    soil_type INT DEFAULT NULL,
    soil_image VARCHAR(255) DEFAULT NULL,
    soil_preparation_details INT DEFAULT NULL,
    soil_preparation_image VARCHAR(255) DEFAULT NULL,
    cane_variety INT DEFAULT NULL,
    cane_variety_image VARCHAR(255) DEFAULT NULL,
    planting_details INT DEFAULT NULL,
    planting_image VARCHAR(255) DEFAULT NULL,
    watering_details INT DEFAULT NULL,
    watering_image VARCHAR(255) DEFAULT NULL,
    germination_percentage INT DEFAULT NULL,
    germination_image VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
SELECT DISTINCT production_year FROM soil_data ORDER BY production_year DESC;
CREATE TABLE IF NOT EXISTS production_years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year_label VARCHAR(10) UNIQUE NOT NULL
);

INSERT INTO production_years (year_label) VALUES
('68-69'),
('69-70'),
('70-71'),
('71-72'),
('72-73');