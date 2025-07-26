-- Carfify PostgreSQL Datenbank Setup V1.3
DROP TABLE IF EXISTS vehicle_sales, diagnosis_questions, diagnosis_sessions, vehicles, workshops, price_tracking CASCADE;

CREATE TABLE vehicles (
    id SERIAL PRIMARY KEY,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL CHECK (year >= 1900 AND year <= EXTRACT(YEAR FROM NOW()) + 1),
    fuel_type VARCHAR(20) CHECK (fuel_type IN ('Benzin', 'Diesel', 'Elektro', 'Hybrid', 'Plug-in-Hybrid', 'Gas', 'Wasserstoff')),
    transmission VARCHAR(20) CHECK (transmission IN ('Schaltgetriebe', 'Automatik', 'CVT', 'Doppelkupplung')),
    engine_size INT, -- Hubraum in ccm
    power_kw INT, -- Leistung in kW
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE diagnosis_sessions (
    id SERIAL PRIMARY KEY,
    session_uuid UUID DEFAULT gen_random_uuid() NOT NULL UNIQUE,
    vehicle_id INT REFERENCES vehicles(id),
    symptoms TEXT[], -- Array von Symptom-Strings
    diagnosis_result JSONB,
    estimated_cost NUMERIC(10,2),
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE diagnosis_questions (
    id SERIAL PRIMARY KEY,
    question_de TEXT NOT NULL,
    question_en TEXT NOT NULL,
    symptom_category VARCHAR(50),
    priority INT DEFAULT 1
);

CREATE TABLE workshops (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(200) NOT NULL,
    lat NUMERIC(10,8) NOT NULL,
    lng NUMERIC(11,8) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    specialties TEXT[], -- Array von Spezialisierungen
    rating NUMERIC(2,1) CHECK (rating >= 0 AND rating <= 5),
    review_count INT DEFAULT 0,
    price_range VARCHAR(20), -- '€', '€€', '€€€'
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE price_tracking (
    id SERIAL PRIMARY KEY,
    session_uuid UUID REFERENCES diagnosis_sessions(session_uuid),
    workshop_id INT REFERENCES workshops(id),
    service_type VARCHAR(50),
    estimated_price NUMERIC(10,2),
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- NEUE TABELLE für Fahrzeugverkäufe
CREATE TABLE vehicle_sales (
    id SERIAL PRIMARY KEY,
    user_id INT, -- Später für registrierte Nutzer
    session_uuid UUID DEFAULT gen_random_uuid() NOT NULL UNIQUE,
    vehicle_id INT NOT NULL REFERENCES vehicles(id),
    mileage INT NOT NULL CHECK (mileage >= 0),
    vehicle_condition_report TEXT, -- JSON-String mit Zustandsdetails
    image_urls TEXT[], -- Array von Bild-URLs
    asking_price NUMERIC(10, 2),
    final_price NUMERIC(10, 2),
    status VARCHAR(20) DEFAULT 'DRAFT' CHECK (status IN ('DRAFT', 'ACTIVE', 'SOLD')),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    sold_at TIMESTAMPTZ
);

-- Beispiel-Workshops einfügen
INSERT INTO workshops (name, address, lat, lng, phone, specialties, rating, price_range) VALUES
('Kfz Meister Schmidt', 'Hauptstraße 123, 10115 Berlin', 52.5200, 13.4050, '030 12345678', ARRAY['Motor', 'Getriebe'], 4.5, '€€'),
('AutoService Müller', 'Berliner Allee 45, 40212 Düsseldorf', 51.2277, 6.7735, '0211 9876543', ARRAY['Elektrik', 'Bremsen'], 4.2, '€'),
('Premium Autowerkstatt', 'Marienplatz 8, 80331 München', 48.1371, 11.5754, '089 5551234', ARRAY['Luxusfahrzeuge', 'Diagnose'], 4.8, '€€€');

-- Beispiel-Fahrzeuge
INSERT INTO vehicles (make, model, year, fuel_type, transmission, engine_size, power_kw) VALUES
('Volkswagen', 'Golf', 2020, 'Benzin', 'Automatik', 1395, 110),
('BMW', '3er', 2022, 'Diesel', 'Automatik', 1995, 140),
('Audi', 'A4', 2021, 'Benzin', 'Schaltgetriebe', 1984, 150);