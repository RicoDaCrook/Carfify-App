-- Carfify Datenbank-Setup
-- Erstellt die komplette PostgreSQL-Datenbankstruktur

-- Datenbank erstellen (falls noch nicht vorhanden)
CREATE DATABASE carfify;

-- Verbindung zur neuen Datenbank herstellen
\c carfify;

-- ENUM-Typ für Workshop-Typen erstellen
CREATE TYPE workshop_type AS ENUM ('FREI', 'VERTRAG', 'KETTE', 'SPEZIALIST');

-- Fahrzeuge-Tabelle
CREATE TABLE IF NOT EXISTS vehicles (
    id SERIAL PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    vin VARCHAR(17) UNIQUE,
    license_plate VARCHAR(20),
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year INTEGER CHECK (year >= 1900 AND year <= EXTRACT(YEAR FROM CURRENT_DATE) + 1),
    fuel_type VARCHAR(50),
    transmission VARCHAR(50),
    mileage INTEGER DEFAULT 0 CHECK (mileage >= 0),
    color VARCHAR(50),
    image_urls TEXT[],
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Diagnose-Sessions-Tabelle
CREATE TABLE IF NOT EXISTS diagnosis_sessions (
    id SERIAL PRIMARY KEY,
    vehicle_id INTEGER REFERENCES vehicles(id) ON DELETE CASCADE,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP WITH TIME ZONE,
    diagnosis_result JSONB
);

-- Diagnose-Fragen-Tabelle
CREATE TABLE IF NOT EXISTS diagnosis_questions (
    id SERIAL PRIMARY KEY,
    session_id INTEGER REFERENCES diagnosis_sessions(id) ON DELETE CASCADE,
    question_text TEXT NOT NULL,
    question_type VARCHAR(50) DEFAULT 'text',
    options JSONB,
    answer JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    answered_at TIMESTAMP WITH TIME ZONE
);

-- Werkstätten-Tabelle
CREATE TABLE IF NOT EXISTS workshops (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    workshop_type workshop_type NOT NULL,
    address TEXT NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    phone VARCHAR(50),
    email VARCHAR(255),
    website VARCHAR(255),
    opening_hours JSONB,
    services TEXT[],
    rating DECIMAL(2, 1) CHECK (rating >= 0 AND rating <= 5),
    review_count INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Preis-Tracking-Tabelle
CREATE TABLE IF NOT EXISTS price_tracking (
    id SERIAL PRIMARY KEY,
    vehicle_id INTEGER REFERENCES vehicles(id) ON DELETE CASCADE,
    service_type VARCHAR(100) NOT NULL,
    estimated_price DECIMAL(10, 2),
    min_price DECIMAL(10, 2),
    max_price DECIMAL(10, 2),
    currency VARCHAR(3) DEFAULT 'EUR',
    region VARCHAR(100),
    source VARCHAR(255),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Fahrzeug-Verkäufe-Tabelle
CREATE TABLE IF NOT EXISTS vehicle_sales (
    id SERIAL PRIMARY KEY,
    vehicle_id INTEGER REFERENCES vehicles(id) ON DELETE CASCADE,
    sale_price DECIMAL(12, 2),
    market_value DECIMAL(12, 2),
    sale_date DATE,
    buyer_info JSONB,
    sale_platform VARCHAR(100),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Indizes für bessere Performance
CREATE INDEX IF NOT EXISTS idx_vehicles_user_id ON vehicles(user_id);
CREATE INDEX IF NOT EXISTS idx_vehicles_vin ON vehicles(vin);
CREATE INDEX IF NOT EXISTS idx_diagnosis_sessions_vehicle_id ON diagnosis_sessions(vehicle_id);
CREATE INDEX IF NOT EXISTS idx_diagnosis_sessions_token ON diagnosis_sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_diagnosis_questions_session_id ON diagnosis_questions(session_id);
CREATE INDEX IF NOT EXISTS idx_workshops_type ON workshops(workshop_type);
CREATE INDEX IF NOT EXISTS idx_workshops_location ON workshops(latitude, longitude);
CREATE INDEX IF NOT EXISTS idx_price_tracking_vehicle_id ON price_tracking(vehicle_id);
CREATE INDEX IF NOT EXISTS idx_vehicle_sales_vehicle_id ON vehicle_sales(vehicle_id);

-- Trigger für updated_at Felder
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_vehicles_updated_at BEFORE UPDATE ON vehicles
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_workshops_updated_at BEFORE UPDATE ON workshops
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();