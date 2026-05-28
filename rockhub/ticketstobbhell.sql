-- ============================================================
--  ticketstobbhell (bbhell) – Baza danych MySQL
--  Silnik: InnoDB | Kodowanie: utf8mb4
--  Autorzy: Jakub Just, Dawid Eisen
-- ============================================================

CREATE DATABASE IF NOT EXISTS ticketstobbhell
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ticketstobbhell;

-- ============================================================
-- 1. UŻYTKOWNICY
-- ============================================================
CREATE TABLE users (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    imie        VARCHAR(64)     NOT NULL,
    email       VARCHAR(128)    NOT NULL UNIQUE,
    haslo_hash  VARCHAR(255)    NOT NULL,          -- password_hash() w PHP
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. ARTYŚCI
-- ============================================================
CREATE TABLE artists (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nazwa       VARCHAR(128)    NOT NULL,
    gatunek     VARCHAR(64)     NOT NULL,          -- Rock, Pop, Hip-Hop, Jazz …
    opis        TEXT,
    zdjecie_url VARCHAR(512),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. MIEJSCA WYDARZEŃ (venue)
-- ============================================================
CREATE TABLE venues (
    id           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    nazwa        VARCHAR(128)   NOT NULL,
    miasto       VARCHAR(64)    NOT NULL,
    adres        VARCHAR(255)   NOT NULL,
    pojemnosc    INT UNSIGNED   NOT NULL,          -- łączna liczba miejsc
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. WYDARZENIA (koncerty)
-- ============================================================
CREATE TABLE events (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    artist_id       INT UNSIGNED    NOT NULL,
    venue_id        INT UNSIGNED    NOT NULL,
    nazwa           VARCHAR(255)    NOT NULL,
    opis            TEXT,
    data_czas       DATETIME        NOT NULL,
    plakat_url      VARCHAR(512),
    -- pula miejsc
    miejsca_siedzace   INT UNSIGNED NOT NULL DEFAULT 0,
    miejsca_stojace    INT UNSIGNED NOT NULL DEFAULT 0,
    -- ceny
    cena_siedzace   DECIMAL(8,2)    NOT NULL,
    cena_stojace    DECIMAL(8,2)    NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_event_artist FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_venue  FOREIGN KEY (venue_id)  REFERENCES venues(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. ZAMÓWIENIA
-- ============================================================
CREATE TABLE orders (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED    NOT NULL,
    event_id        INT UNSIGNED    NOT NULL,
    strefa          ENUM('siedzace','stojace') NOT NULL,
    ilosc           TINYINT UNSIGNED NOT NULL DEFAULT 1,
    cena_jednostkowa DECIMAL(8,2)  NOT NULL,       -- cena w chwili zakupu
    suma            DECIMAL(10,2)  NOT NULL,        -- ilosc * cena_jednostkowa
    status          ENUM('oczekuje','oplacone','anulowane') NOT NULL DEFAULT 'oczekuje',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_order_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_order_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 6. ULUBIENI ARTYŚCI
-- ============================================================
CREATE TABLE user_favourite_artists (
    user_id     INT UNSIGNED    NOT NULL,
    artist_id   INT UNSIGNED    NOT NULL,
    added_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, artist_id),
    CONSTRAINT fk_fav_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    CONSTRAINT fk_fav_artist FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- WIDOKI (pomocne przy wyświetlaniu)
-- ============================================================

-- Widok: lista wydarzeń z nazwą artysty i miejsca
CREATE VIEW v_events_full AS
SELECT
    e.id,
    e.nazwa            AS koncert,
    a.nazwa            AS artysta,
    a.gatunek,
    v.nazwa            AS miejsce,
    v.miasto,
    v.adres,
    e.data_czas,
    e.plakat_url,
    e.miejsca_siedzace,
    e.miejsca_stojace,
    e.cena_siedzace,
    e.cena_stojace
FROM events e
JOIN artists a ON e.artist_id = a.id
JOIN venues  v ON e.venue_id  = v.id;

-- Widok: dostępne miejsca (po odjęciu sprzedanych)
CREATE VIEW v_dostepnosc AS
SELECT
    e.id                                                           AS event_id,
    e.miejsca_siedzace - COALESCE(SUM(CASE WHEN o.strefa = 'siedzace'
        THEN o.ilosc ELSE 0 END), 0)                              AS wolne_siedzace,
    e.miejsca_stojace  - COALESCE(SUM(CASE WHEN o.strefa = 'stojace'
        THEN o.ilosc ELSE 0 END), 0)                              AS wolne_stojace
FROM events e
LEFT JOIN orders o ON o.event_id = e.id AND o.status = 'oplacone'
GROUP BY e.id, e.miejsca_siedzace, e.miejsca_stojace;

-- ============================================================
-- DANE PRZYKŁADOWE
-- ============================================================

INSERT INTO artists (nazwa, gatunek, opis, zdjecie_url) VALUES
('Metallica',     'Metal',     'Legenda heavy metalu z San Francisco.',       'img/metallica.jpg'),
('Dua Lipa',      'Pop',       'Brytyjska gwiazda pop i dance.',              'img/dua_lipa.jpg'),
('Taco Hemingway','Hip-Hop',   'Polski raper, autor tekstów z Warszawy.',     'img/taco.jpg'),
('Dawid Podsiadło','Pop/Rock', 'Jeden z najpopularniejszych artystów w PL.',  'img/podsiadlo.jpg'),
('Calvin Harris', 'Electronic','Szkocki DJ i producent muzyczny.',            'img/calvin.jpg');

INSERT INTO venues (nazwa, miasto, adres, pojemnosc) VALUES
('Tauron Arena',     'Kraków',  'ul. Lema 7',            17000),
('PGE Narodowy',     'Warszawa','al. Księcia J. Poniatowskiego 1', 58500),
('Hala Stulecia',    'Wrocław', 'ul. Wystawowa 1',       11000),
('Atlas Arena',      'Łódź',    'al. Bandurskiego 7',    13500),
('Ergo Arena',       'Gdańsk',  'pl. Dwóch Miast 1',     11000);

INSERT INTO events (artist_id, venue_id, nazwa, opis, data_czas,
    miejsca_siedzace, miejsca_stojace, cena_siedzace, cena_stojace) VALUES
(1, 1, 'Metallica – M72 World Tour',
    'Kultowa trasa Metalliki z nowym repertuarem.',
    '2025-08-15 20:00:00', 5000, 12000, 399.00, 249.00),

(2, 2, 'Dua Lipa – Radical Optimism Tour',
    'Europejska trasa po premierowym albumie.',
    '2025-09-03 19:30:00', 20000, 38500, 299.00, 199.00),

(3, 3, 'Taco Hemingway – Trójkąt Warszawski Live',
    'Koncert promujący najnowszą płytę rapera.',
    '2025-07-20 19:00:00', 3000, 8000,  149.00,  99.00),

(4, 4, 'Dawid Podsiadło – Małomiasteczkowy Tour',
    'Wielkie widowisko na żywo.',
    '2025-10-11 20:00:00', 4000, 9500,  189.00, 129.00),

(5, 5, 'Calvin Harris – Summer Rave',
    'Wieczór pełen elektronicznych brzmień.',
    '2025-08-30 22:00:00', 2000, 9000,  219.00, 169.00);

-- ============================================================
-- PRZYKŁADOWY UŻYTKOWNIK (hasło: Test1234!)
-- password_hash('Test1234!', PASSWORD_BCRYPT) w PHP
-- ============================================================
INSERT INTO users (imie, email, haslo_hash) VALUES
('Jan Kowalski', 'jan@example.com',
 '$2y$10$u5Dv7gQz1kXoWvNhL2mBOeHs3pYqA8RwTbCjIeMfGdKlPnSxVtUr2');
