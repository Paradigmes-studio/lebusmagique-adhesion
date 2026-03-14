-- Fixtures for local development
-- Load with: make fixtures

SET NAMES utf8mb4;

-- Clean existing data
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE adh_adhesion_client;
TRUNCATE TABLE adh_adhesion_type_description;
TRUNCATE TABLE adh_adhesion_type;
TRUNCATE TABLE adh_mailchimp_tag;
DELETE FROM adh_user WHERE login != 'admin';
SET FOREIGN_KEY_CHECKS = 1;

-- Adhesion types
INSERT INTO adh_adhesion_type (name, price, email_welcome, duration) VALUES
('Adhésion à prix libre', 0.00, 'Adhesion.html', 12);

-- Adhesion type descriptions (FR)
INSERT INTO adh_adhesion_type_description (adhesion_type, lang, description) VALUES
(1, 'fr', 'Adhésion à prix libre pour soutenir le Bus Magique');

-- Adhesion clients
INSERT INTO adh_adhesion_client (last_name, first_name, email, adhesion_type, date_debut, date_fin, newsletter) VALUES
('Dupont', 'Marie', 'marie.dupont@example.com', 'Adhésion à prix libre', '2025-09-01 00:00:00', '2026-09-01 00:00:00', 1),
('Martin', 'Lucas', 'lucas.martin@example.com', 'Adhésion à prix libre', '2025-10-15 00:00:00', '2026-10-15 00:00:00', 1),
('Bernard', 'Sophie', 'sophie.bernard@example.com', 'Adhésion à prix libre', '2025-11-01 00:00:00', '2026-11-01 00:00:00', 0),
('Petit', 'Thomas', 'thomas.petit@example.com', 'Adhésion à prix libre', '2025-06-01 00:00:00', '2026-06-01 00:00:00', 1),
('Durand', 'Camille', 'camille.durand@example.com', 'Adhésion à prix libre', '2025-12-01 00:00:00', '2026-12-01 00:00:00', 1),
('Leroy', 'Antoine', 'antoine.leroy@example.com', 'Adhésion à prix libre', '2026-01-10 00:00:00', '2027-01-10 00:00:00', 0),
('Moreau', 'Julie', 'julie.moreau@example.com', 'Adhésion à prix libre', '2026-02-01 00:00:00', '2027-02-01 00:00:00', 1),
('Simon', 'Hugo', 'hugo.simon@example.com', 'Adhésion à prix libre', '2025-08-15 00:00:00', '2026-08-15 00:00:00', 1),
('Laurent', 'Emma', 'emma.laurent@example.com', 'Adhésion à prix libre', '2025-07-01 00:00:00', '2026-07-01 00:00:00', 0),
('Lefebvre', 'Pierre', 'pierre.lefebvre@example.com', 'Adhésion à prix libre', '2026-03-01 00:00:00', '2027-03-01 00:00:00', 1),
('Garcia', 'Lea', 'lea.garcia@example.com', 'Adhésion à prix libre', '2025-09-15 00:00:00', '2026-09-15 00:00:00', 1),
('Roux', 'Nathan', 'nathan.roux@example.com', 'Adhésion à prix libre', '2025-05-01 00:00:00', '2026-05-01 00:00:00', 0),
('Fournier', 'Chloe', 'chloe.fournier@example.com', 'Adhésion à prix libre', '2026-01-01 00:00:00', '2027-01-01 00:00:00', 1),
('Girard', 'Maxime', 'maxime.girard@example.com', 'Adhésion à prix libre', '2025-11-15 00:00:00', '2026-11-15 00:00:00', 1),
('Bonnet', 'Manon', 'manon.bonnet@example.com', 'Adhésion à prix libre', '2025-04-01 00:00:00', '2026-04-01 00:00:00', 0),
('Lambert', 'Alexandre', 'alexandre.lambert@example.com', 'Adhésion à prix libre', '2025-03-01 00:00:00', '2026-03-01 00:00:00', 1),
('Fontaine', 'Ines', 'ines.fontaine@example.com', 'Adhésion à prix libre', '2026-02-15 00:00:00', '2027-02-15 00:00:00', 1),
('Mercier', 'Louis', 'louis.mercier@example.com', 'Adhésion à prix libre', '2025-10-01 00:00:00', '2026-10-01 00:00:00', 0),
('Blanc', 'Clara', 'clara.blanc@example.com', 'Adhésion à prix libre', '2025-12-15 00:00:00', '2026-12-15 00:00:00', 1),
('Robin', 'Paul', 'paul.robin@example.com', 'Adhésion à prix libre', '2026-03-10 00:00:00', '2027-03-10 00:00:00', 1),
('Sancassani', 'Celine', 'jmsaliou79@gmail.com', 'Adhésion à prix libre', '2024-05-18 00:00:00', '2024-05-18 00:00:00', 0);

-- Mailchimp tags
INSERT INTO adh_mailchimp_tag (name, active) VALUES
('Adherent actif', 1),
('Newsletter', 1),
('Ancien adherent', 0);

-- Extra admin user
INSERT INTO adh_user (login, password) VALUES
('demo', '$2y$10$YfQYFvOtKlBqMz0lMn5aZ.PwVnYkhGNOhGUFMVrFDWksmVwjnkHSe');
-- password: demo
