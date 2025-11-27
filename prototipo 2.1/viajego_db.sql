CREATE DATABASE  IF NOT EXISTS `viajego_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `viajego_db`;
-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: viajego_db
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bus_services`
--

DROP TABLE IF EXISTS `bus_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bus_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `bus_type` varchar(50) DEFAULT NULL,
  `origin_id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `departure_time` datetime NOT NULL,
  `arrival_time` datetime NOT NULL,
  `price_per_seat` decimal(10,2) NOT NULL,
  `total_seats` int(11) NOT NULL,
  `amenities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`amenities`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_bus_origin` (`origin_id`),
  KEY `fk_bus_destination` (`destination_id`),
  KEY `fk_bus_agency` (`agency_id`),
  CONSTRAINT `fk_bus_agency` FOREIGN KEY (`agency_id`) REFERENCES `travel_agencies` (`id`),
  CONSTRAINT `fk_bus_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`),
  CONSTRAINT `fk_bus_origin` FOREIGN KEY (`origin_id`) REFERENCES `destinations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bus_services`
--

LOCK TABLES `bus_services` WRITE;
/*!40000 ALTER TABLE `bus_services` DISABLE KEYS */;
INSERT INTO `bus_services` VALUES (1,2,'ADO Platino','Lujo',5,8,'2025-11-28 18:50:14','2025-11-29 00:50:14',1200.00,25,'[\"Pantalla individual\", \"Cafetería\", \"Baño AyH\"]',1,'2025-11-26 18:50:14'),(2,2,'ETN','Ejecutivo',5,6,'2025-11-29 18:50:14','2025-11-30 01:50:14',1100.00,30,'[\"Asientos cama\", \"Wifi\", \"Lunch al abordar\"]',1,'2025-11-26 18:50:14'),(3,2,'Primera Plus','Primera Clase',6,3,'2025-11-30 18:50:14','2025-11-30 22:50:14',800.00,40,'[\"Aire Acondicionado\", \"2 Baños\", \"Pantallas\"]',1,'2025-11-26 18:50:14');
/*!40000 ALTER TABLE `bus_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `destinations`
--

DROP TABLE IF EXISTS `destinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `destinations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `type` enum('Playa','Ciudad','Aventura') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `destinations`
--

LOCK TABLES `destinations` WRITE;
/*!40000 ALTER TABLE `destinations` DISABLE KEYS */;
INSERT INTO `destinations` VALUES (1,'Cancún','Playa'),(2,'Los Cabos','Playa'),(3,'Puerto Vallarta','Playa'),(4,'Huatulco','Playa'),(5,'Ciudad de México','Ciudad'),(6,'Guadalajara','Ciudad'),(7,'Monterrey','Ciudad'),(8,'Oaxaca','Ciudad'),(9,'Mérida','Ciudad'),(10,'San Miguel de Allende','Ciudad'),(11,'San Cristóbal de las Casas','Ciudad'),(12,'Barrancas del Cobre','Aventura');
/*!40000 ALTER TABLE `destinations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flight_services`
--

DROP TABLE IF EXISTS `flight_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flight_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) NOT NULL,
  `airline_name` varchar(255) NOT NULL,
  `flight_number` varchar(50) NOT NULL,
  `aircraft_type` varchar(100) DEFAULT NULL,
  `flight_class` varchar(50) NOT NULL,
  `origin_id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `departure_time` datetime NOT NULL,
  `arrival_time` datetime NOT NULL,
  `price_per_ticket` decimal(10,2) NOT NULL,
  `total_seats` int(11) NOT NULL,
  `baggage_policy` text DEFAULT NULL,
  `is_round_trip` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_flight_origin` (`origin_id`),
  KEY `fk_flight_destination` (`destination_id`),
  KEY `fk_flight_agency` (`agency_id`),
  CONSTRAINT `fk_flight_agency` FOREIGN KEY (`agency_id`) REFERENCES `travel_agencies` (`id`),
  CONSTRAINT `fk_flight_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`),
  CONSTRAINT `fk_flight_origin` FOREIGN KEY (`origin_id`) REFERENCES `destinations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flight_services`
--

LOCK TABLES `flight_services` WRITE;
/*!40000 ALTER TABLE `flight_services` DISABLE KEYS */;
INSERT INTO `flight_services` VALUES (1,1,'Aeromexico','AM500','Boeing 737','Turista',5,1,'2025-12-01 18:50:14','2025-12-01 20:50:14',3500.00,150,'1 maleta 25kg documentada',1,1,'2025-11-26 18:50:14'),(2,1,'Volaris','Y4-100','Airbus A320','Turista',6,1,'2025-12-02 18:50:14','2025-12-02 21:50:14',2800.00,180,'Solo equipaje de mano 10kg',1,1,'2025-11-26 18:50:14'),(3,1,'VivaAerobus','VB202','Airbus A321','Turista',7,2,'2025-12-03 18:50:14','2025-12-03 20:50:14',2200.00,200,'Tarifa Zero (solo artículo personal)',1,1,'2025-11-26 18:50:14'),(4,2,'Aeromexico','AM305','Embraer 190','Premier',5,8,'2025-11-30 18:50:14','2025-11-30 19:50:14',4100.00,90,'2 maletas 32kg y prioridad',1,1,'2025-11-26 18:50:14'),(5,2,'Volaris','Y4-550','Airbus A320','Turista',1,5,'2025-12-06 18:50:14','2025-12-06 20:50:14',1900.00,180,'Equipaje de mano estándar',0,1,'2025-11-26 18:50:14');
/*!40000 ALTER TABLE `flight_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hotels`
--

DROP TABLE IF EXISTS `hotels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hotels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `price_per_night` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `stars` int(11) DEFAULT 3,
  `agency_id` int(11) DEFAULT NULL,
  `destination_id` int(11) NOT NULL,
  `regimen` enum('Solo Alojamiento','Desayuno','Todo Incluido') NOT NULL DEFAULT 'Solo Alojamiento',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_hotel_agency` (`agency_id`),
  KEY `fk_hotel_destination` (`destination_id`),
  CONSTRAINT `fk_hotel_agency` FOREIGN KEY (`agency_id`) REFERENCES `travel_agencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_hotel_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hotels`
--

LOCK TABLES `hotels` WRITE;
/*!40000 ALTER TABLE `hotels` DISABLE KEYS */;
INSERT INTO `hotels` VALUES (1,'Grand Oasis Cancún',4500.00,'Resort de entretenimiento con vista al mar Caribe.',5,1,1,'Todo Incluido','2025-11-26 18:50:14'),(2,'Hotel Riu Palace Las Americas',6200.00,'Exclusivo solo adultos en el corazón de la zona hotelera.',5,1,1,'Todo Incluido','2025-11-26 18:50:14'),(3,'Krystal Urban Cancún',1800.00,'Opción moderna en el centro, ideal para negocios y placer.',4,1,1,'Desayuno','2025-11-26 18:50:14'),(4,'Riu Santa Fe',5100.00,'Vistas al Arco y fiestas en la piscina.',5,1,2,'Todo Incluido','2025-11-26 18:50:14'),(5,'Tesoro Los Cabos',2800.00,'Ubicado en la marina, cerca de todo.',4,1,2,'Desayuno','2025-11-26 18:50:14'),(6,'Hard Rock Hotel Los Cabos',7500.00,'Lujo y música frente al Pacífico.',5,1,2,'Todo Incluido','2025-11-26 18:50:14'),(7,'Sheraton Buganvilias',3900.00,'Resort clásico con excelente ubicación.',5,1,3,'Todo Incluido','2025-11-26 18:50:14'),(8,'Hotel Tropicana',1500.00,'Tradicional, frente a la playa de los muertos.',3,1,3,'Solo Alojamiento','2025-11-26 18:50:14'),(9,'Velas Vallarta',5800.00,'Suites de lujo para familias.',5,1,3,'Todo Incluido','2025-11-26 18:50:14'),(10,'Barceló México Reforma',3200.00,'Lujo moderno en el paseo de la Reforma.',5,2,5,'Desayuno','2025-11-26 18:50:14'),(11,'Hotel Histórico Central',2100.00,'Encanto colonial a pasos del Zócalo.',4,2,5,'Desayuno','2025-11-26 18:50:14'),(12,'Ibis Alameda',1100.00,'Práctico y económico en el centro.',3,2,5,'Solo Alojamiento','2025-11-26 18:50:14'),(13,'Hotel Riu Plaza Guadalajara',2900.00,'El rascacielos icónico de la ciudad.',5,2,6,'Desayuno','2025-11-26 18:50:14'),(14,'Hotel Morales',1800.00,'Historia taurina en el centro histórico.',4,2,6,'Solo Alojamiento','2025-11-26 18:50:14'),(15,'Krystal Urban Guadalajara',1400.00,'Cerca de la zona expo.',3,2,6,'Desayuno','2025-11-26 18:50:14'),(16,'Quinta Real Oaxaca',4800.00,'Un ex-convento convertido en hotel de lujo.',5,2,8,'Desayuno','2025-11-26 18:50:14'),(17,'Hotel Misión de los Ángeles',1900.00,'Jardines amplios y ambiente relajado.',4,2,8,'Solo Alojamiento','2025-11-26 18:50:14'),(18,'Hostal de la Noria',2500.00,'A dos cuadras del zócalo, estilo colonial.',4,2,8,'Desayuno','2025-11-26 18:50:14');
/*!40000 ALTER TABLE `hotels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packages`
--

DROP TABLE IF EXISTS `packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT 0.00,
  `destination_id` int(11) NOT NULL,
  `agency_id` int(11) NOT NULL,
  `includes_transport` text DEFAULT NULL,
  `includes_lodging` text DEFAULT NULL,
  `includes_extras` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_pkg_destination` (`destination_id`),
  KEY `fk_pkg_agency` (`agency_id`),
  CONSTRAINT `fk_pkg_agency` FOREIGN KEY (`agency_id`) REFERENCES `travel_agencies` (`id`),
  CONSTRAINT `fk_pkg_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packages`
--

LOCK TABLES `packages` WRITE;
/*!40000 ALTER TABLE `packages` DISABLE KEYS */;
INSERT INTO `packages` VALUES (1,'Escapada Romántica a Cancún','Disfruta de 5 días y 4 noches en el paraíso del Caribe con tu pareja.','cancun.jpg',28500.00,1,1,'Vuelo redondo CDMX-Cancún con Aeromexico. Maleta documentada incluida.','4 Noches en Hotel Riu Palace Las Americas (Solo Adultos), Habitación Junior Suite.','Traslado privado Aeropuerto-Hotel, Cena romántica en la playa, Seguro de viajero.'),(2,'Aventura Cultural en Oaxaca','Sumérgete en las tradiciones, gastronomía y colores de Oaxaca.','oaxaca.jpg',12500.00,8,2,'Autobús de Lujo ADO Platino (Redondo desde CDMX), viaja de noche y descansa.','3 Noches en Hotel Misión de los Ángeles, rodeado de jardines.','Tour a Monte Albán y Hierve el Agua, Degustación de Mezcal, Desayunos incluidos.'),(3,'Fin de Semana en Los Cabos','Lujo y desierto se unen en este viaje express de 4 días.','loscabos.jpg',19900.00,2,1,'Vuelo redondo MTY-Los Cabos con VivaAerobus.','3 Noches en Riu Santa Fe, plan Todo Incluido 24 horas.','Acceso a fiestas Riu Party, transporte compartido al hotel.'),(4,'Guadalajara Moderna y Tradicional','Conoce la tierra del Tequila y el Mariachi.','gdl.jpg',8500.00,6,2,'Vuelo redondo desde CDMX o MTY (según disponibilidad).','3 Noches en Krystal Urban Guadalajara, ubicación estratégica.','Tour al pueblo mágico de Tequila en tren, Entrada al Hospicio Cabañas.');
/*!40000 ALTER TABLE `packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `package_id` int(11) DEFAULT NULL,
  `hotel_id` int(11) DEFAULT NULL,
  `flight_id` int(11) DEFAULT NULL,
  `bus_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `adults` int(11) DEFAULT 1,
  `children` int(11) DEFAULT 0,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'confirmed',
  `created_at` datetime DEFAULT current_timestamp(),
  `selected_class` varchar(50) DEFAULT 'Economy',
  `extra_baggage` int(11) DEFAULT 0,
  `seat_preference` varchar(20) DEFAULT 'Cualquiera',
  `room_type` varchar(50) DEFAULT 'Estándar',
  `board_basis` varchar(50) DEFAULT 'Solo Alojamiento',
  `travel_class` varchar(50) DEFAULT 'Turista',
  `extras` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `reservations_ibfk_2` (`package_id`),
  KEY `reservations_ibfk_3` (`hotel_id`),
  KEY `reservations_ibfk_4` (`flight_id`),
  KEY `reservations_ibfk_5` (`bus_id`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reservations_ibfk_4` FOREIGN KEY (`flight_id`) REFERENCES `flight_services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reservations_ibfk_5` FOREIGN KEY (`bus_id`) REFERENCES `bus_services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (1,2,4,13,NULL,NULL,'2025-11-27','2025-11-30',2,5,22600.00,'paid','2025-11-26 19:09:55','Economy',0,'Cualquiera','Estándar','Solo Alojamiento','Turista',NULL),(2,2,2,17,NULL,NULL,'2025-11-27','2025-11-30',2,3,22000.00,'paid','2025-11-26 19:12:41','Economy',0,'Cualquiera','Estándar','Solo Alojamiento','Turista',NULL),(3,2,2,18,NULL,NULL,'2025-11-27','2025-11-30',2,0,23500.00,'paid','2025-11-26 19:22:34','Economy',0,'Cualquiera','Estándar','Solo Alojamiento','Turista',NULL),(4,2,NULL,12,NULL,NULL,'2025-11-27','2025-11-28',1,0,1100.00,'paid','2025-11-26 19:41:36','Economy',0,'Cualquiera','Estándar','Solo Alojamiento','Turista',NULL);
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sesiones`
--

DROP TABLE IF EXISTS `sesiones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sesiones` (
  `id` varchar(255) NOT NULL,
  `datos` longtext NOT NULL,
  `expiracion` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sesiones`
--

LOCK TABLES `sesiones` WRITE;
/*!40000 ALTER TABLE `sesiones` DISABLE KEYS */;
INSERT INTO `sesiones` VALUES ('ourldsbpcaua3bmgko80ru6cte','',1764208584),('tmmeb943qhpnvti6k205r9gpv2','user_id|i:2;username|s:4:\"rayo\";user_email|s:14:\"rayo@gmail.com\";user_role|s:4:\"user\";',1764215047);
/*!40000 ALTER TABLE `sesiones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `travel_agencies`
--

DROP TABLE IF EXISTS `travel_agencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `travel_agencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `travel_agencies`
--

LOCK TABLES `travel_agencies` WRITE;
/*!40000 ALTER TABLE `travel_agencies` DISABLE KEYS */;
INSERT INTO `travel_agencies` VALUES (1,'Caribe & Sol Travel','Especialistas en destinos de playa y resorts todo incluido.','reservas@csol.com',NULL,NULL,'4521250ac055e0913eca7aa198e1f43cbfa72760b2920a2f005a04fd35b6c9c8',1,'2025-11-26 18:50:14'),(2,'México Mágico Tours','Expertos en turismo cultural, ciudades coloniales y aventura.','info@mmtours.com',NULL,NULL,'8b661ce0b2933b6d674f70fb30c8144f1b05a82546f1b335600065a8e1d34c89',1,'2025-11-26 18:50:14');
/*!40000 ALTER TABLE `travel_agencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','editor','agencia','user') NOT NULL DEFAULT 'user',
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'SuperAdmin','admin@viajego.com','AQUI_PEGAS_EL_HASH_GENERADO','admin','2025-11-26 18:45:40',NULL),(2,'rayo','rayo@gmail.com','$2y$10$chgCqX.4C.QJG1YNpLi8feM/hhOqV1f44bh90ec4qEbhdskSKfye6','user','2025-11-26 18:59:30',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-26 21:11:31
