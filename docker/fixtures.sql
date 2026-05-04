-- Fixtures for local development
-- Load with: make fixtures

SET NAMES utf8mb4;

-- Clean existing data
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE adh_adhesion_client;
TRUNCATE TABLE adh_adhesion_type_description;
TRUNCATE TABLE adh_adhesion_type;
DELETE FROM adh_user WHERE login != 'admin';
SET FOREIGN_KEY_CHECKS = 1;

-- Adhesion types (email_welcome references adh_email_template.id)
INSERT INTO adh_adhesion_type (name, price, email_welcome, duration) VALUES
('Adhésion à prix libre', 0.00, '1', 365);

-- Adhesion type descriptions (FR)
INSERT INTO adh_adhesion_type_description (adhesion_type, lang, description) VALUES
(1, 'fr', 'Adhésion à prix libre pour soutenir le Bus Magique');

-- Adhesion clients
INSERT INTO adh_adhesion_client (last_name, first_name, email, adhesion_type, date_debut, date_fin, newsletter, referral_source) VALUES
('Dupont', 'Marie', 'marie.dupont@example.com', 'Adhésion à prix libre', '2025-09-01 00:00:00', '2026-09-01 00:00:00', 1, 'passant'),
('Martin', 'Lucas', 'lucas.martin@example.com', 'Adhésion à prix libre', '2025-10-15 00:00:00', '2026-10-15 00:00:00', 1, 'bouche_a_oreille'),
('Bernard', 'Sophie', 'sophie.bernard@example.com', 'Adhésion à prix libre', '2025-11-01 00:00:00', '2026-11-01 00:00:00', 0, 'instagram'),
('Petit', 'Thomas', 'thomas.petit@example.com', 'Adhésion à prix libre', '2025-06-01 00:00:00', '2026-06-01 00:00:00', 1, 'facebook'),
('Durand', 'Camille', 'camille.durand@example.com', 'Adhésion à prix libre', '2025-12-01 00:00:00', '2026-12-01 00:00:00', 1, 'passant'),
('Leroy', 'Antoine', 'antoine.leroy@example.com', 'Adhésion à prix libre', '2026-01-10 00:00:00', '2027-01-10 00:00:00', 0, 'site_web'),
('Moreau', 'Julie', 'julie.moreau@example.com', 'Adhésion à prix libre', '2026-02-01 00:00:00', '2027-02-01 00:00:00', 1, 'bouche_a_oreille'),
('Simon', 'Hugo', 'hugo.simon@example.com', 'Adhésion à prix libre', '2025-08-15 00:00:00', '2026-08-15 00:00:00', 1, 'passant'),
('Laurent', 'Emma', 'emma.laurent@example.com', 'Adhésion à prix libre', '2025-07-01 00:00:00', '2026-07-01 00:00:00', 0, 'instagram'),
('Lefebvre', 'Pierre', 'pierre.lefebvre@example.com', 'Adhésion à prix libre', '2026-03-01 00:00:00', '2027-03-01 00:00:00', 1, 'passant'),
('Garcia', 'Lea', 'lea.garcia@example.com', 'Adhésion à prix libre', '2025-09-15 00:00:00', '2026-09-15 00:00:00', 1, 'bouche_a_oreille'),
('Roux', 'Nathan', 'nathan.roux@example.com', 'Adhésion à prix libre', '2025-05-01 00:00:00', '2026-05-01 00:00:00', 0, 'facebook'),
('Fournier', 'Chloe', 'chloe.fournier@example.com', 'Adhésion à prix libre', '2026-01-01 00:00:00', '2027-01-01 00:00:00', 1, 'passant'),
('Girard', 'Maxime', 'maxime.girard@example.com', 'Adhésion à prix libre', '2025-11-15 00:00:00', '2026-11-15 00:00:00', 1, 'site_web'),
('Bonnet', 'Manon', 'manon.bonnet@example.com', 'Adhésion à prix libre', '2025-04-01 00:00:00', '2026-04-01 00:00:00', 0, 'autre:Festival du Bus'),
('Lambert', 'Alexandre', 'alexandre.lambert@example.com', 'Adhésion à prix libre', '2025-03-01 00:00:00', '2026-03-01 00:00:00', 1, 'bouche_a_oreille'),
('Fontaine', 'Ines', 'ines.fontaine@example.com', 'Adhésion à prix libre', '2026-02-15 00:00:00', '2027-02-15 00:00:00', 1, 'instagram'),
('Mercier', 'Louis', 'louis.mercier@example.com', 'Adhésion à prix libre', '2025-10-01 00:00:00', '2026-10-01 00:00:00', 0, 'passant'),
('Blanc', 'Clara', 'clara.blanc@example.com', 'Adhésion à prix libre', '2025-12-15 00:00:00', '2026-12-15 00:00:00', 1, 'bouche_a_oreille'),
('Robin', 'Paul', 'paul.robin@example.com', 'Adhésion à prix libre', '2026-03-10 00:00:00', '2027-03-10 00:00:00', 1, 'passant'),
('Sancassani', 'Celine', 'jmsaliou79@gmail.com', 'Adhésion à prix libre', '2024-05-18 00:00:00', '2024-05-18 00:00:00', 0, NULL);

-- Extra admin user
INSERT INTO adh_user (login, password) VALUES
('demo', '$2y$10$YfQYFvOtKlBqMz0lMn5aZ.PwVnYkhGNOhGUFMVrFDWksmVwjnkHSe');
-- password: demo
