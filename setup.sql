-- Carfify PostgreSQL Datenbank Setup V1.3
DROP TABLE IF EXISTS vehicle_sales, diagnosis_questions, diagnosis_sessions, vehicles, workshops, price_tracking CASCADE;

CREATE TABLE vehicles (
    id SERIAL PRIMARY KEY,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL CHECK (year BETWEEN 1900 AND EXTRACT(YEAR FROM NOW()) + 1),
    fuel_type VARCHAR(20) CHECK (fuel_type IN ('Benzin', 'Diesel', 'Elektro', 'Hybrid', 'Plug-in-Hybrid', 'LPG', 'CNG')),
    transmission VARCHAR(20) CHECK (transmission IN ('Schaltgetriebe', 'Automatik', 'Direktschalt', 'CVT')),
    engine_size INT, -- in ccm
    power_kw INT, -- in kW
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE diagnosis_sessions (
    id SERIAL PRIMARY KEY,
    session_uuid UUID DEFAULT gen_random_uuid() NOT NULL UNIQUE,
    vehicle_id INT REFERENCES vehicles(id),
    current_step INT DEFAULT 1,
    symptoms TEXT[], -- Array von Symptom-IDs
    answers JSONB DEFAULT '{}',
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE diagnosis_questions (
    id SERIAL PRIMARY KEY,
    step INT NOT NULL,
    question_text_de TEXT NOT NULL,
    question_text_en TEXT,
    options JSONB, -- {"option_value": "option_text"}
    next_step_map JSONB, -- {"option_value": next_step}
    is_final BOOLEAN DEFAULT FALSE,
    possible_diagnoses TEXT[] -- Array von Diagnose-IDs
);

CREATE TABLE workshops (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    lat NUMERIC(9,6),
    lng NUMERIC(9,6),
    phone VARCHAR(30),
    email VARCHAR(100),
    website VARCHAR(255),
    specializations TEXT[], -- z.B. ['Elektrik', 'Motor']
    rating NUMERIC(2,1) CHECK (rating >= 0 AND rating <= 5),
    review_count INT DEFAULT 0,
    price_range VARCHAR(10) CHECK (price_range IN ('€', '€€', '€€€')),
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE price_tracking (
    id SERIAL PRIMARY KEY,
    vehicle_id INT REFERENCES vehicles(id),
    service_type VARCHAR(50) NOT NULL,
    avg_price NUMERIC(8,2),
    price_range_min NUMERIC(8,2),
    price_range_max NUMERIC(8,2),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- NEUE TABELLE für Fahrzeugverkäufe
CREATE TABLE vehicle_sales (
    id SERIAL PRIMARY KEY,
    user_id INT, -- Später für registrierte Nutzer
    session_uuid UUID DEFAULT gen_random_uuid() NOT NULL UNIQUE,
    vehicle_id INT NOT NULL REFERENCES vehicles(id),
    mileage INT NOT NULL,
    vehicle_condition_report TEXT, -- JSON-String mit Zustandsdetails
    image_urls TEXT[], -- Array von Bild-URLs
    asking_price NUMERIC(10, 2),
    final_price NUMERIC(10, 2),
    status VARCHAR(20) DEFAULT 'DRAFT' CHECK (status IN ('DRAFT', 'ACTIVE', 'SOLD')),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    sold_at TIMESTAMPTZ
);

-- Beispieldaten für Diagnose-Fragen
INSERT INTO diagnosis_questions (step, question_text_de, options, next_step_map, is_final) VALUES
(1, 'Was ist das Hauptproblem mit Ihrem Fahrzeug?', '{"engine": "Motorprobleme", "electrics": "Elektrische Probleme", "brakes": "Bremsen", "suspension": "Fahrwerk", "other": "Sonstiges"}', '{"engine": 2, "electrics": 3, "brakes": 4, "suspension": 5, "other": 6}', false),
(2, 'Beschreiben Sie das Motorproblem näher', '{"noise": "Ungewöhnliche Geräusche", "power": "Leistungsverlust", "smoke": "Rauch aus Auspuff", "start": "Startprobleme"}', '{"noise": 20, "power": 21, "smoke": 22, "start": 23}', false),
(20, 'Diagnose: Motorgeräusche - Mögliche Ursache: Ventile oder Zahnriemen', '{}', '{}', true);

-- Beispiel-Workshops
INSERT INTO workshops (name, address, lat, lng, phone, specializations, rating, review_count, price_range) VALUES
('Meister Müller GmbH', 'Hauptstraße 45, 10115 Berlin', 52.5200, 13.4050, '030-12345678', '{"Motor", "Elektrik"}', 4.8, 127, '€€'),
('AutoService Schmidt', 'Berliner Allee 12, 10117 Berlin', 52.5300, 13.4100, '030-87654321', '{"Bremsen", "Fahrwerk"}', 4.5, 89, '€');