-- SAPA V2 - Esquema de Base de Datos Optimizado
-- Resuelve inconsistencias de estudiantes y normaliza la estructura

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------
-- 1. MÓDULO DE USUARIOS Y SEGURIDAD (Sistema Core)
-- ---------------------------------------------------------

-- Tabla de Usuarios Administrativos (Profesores, Gestores, Admin)
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL, -- Hash bcrypt
    `name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'manager', 'teacher') DEFAULT 'teacher',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla Maestra de Estudiantes (Centralizada para todo el sistema)
-- Resuelve el problema de datos duplicados en Notas vs Encuestas
CREATE TABLE `students` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `rut` VARCHAR(20) NOT NULL UNIQUE, -- Identificador único nacional
    `email` VARCHAR(150) UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `entry_year` INT NULL, -- Año de ingreso
    `active` BOOLEAN DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuración de IA (Centralizada)
CREATE TABLE `ai_configs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `provider` VARCHAR(50) NOT NULL, -- 'openai', 'deepseek', 'gemini'
    `api_key` VARCHAR(255) NOT NULL,
    `model` VARCHAR(100) DEFAULT 'gpt-4o',
    `is_active` BOOLEAN DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 2. MÓDULO DE GESTIÓN CURRICULAR
-- ---------------------------------------------------------

CREATE TABLE `profiles` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) UNIQUE, -- Ej: PE-2025
    `name` VARCHAR(200) NOT NULL,
    `career` VARCHAR(200) NOT NULL, -- Carrera
    `faculty` VARCHAR(200),
    `description` TEXT,
    `has_licentiate` BOOLEAN DEFAULT 0, -- Con Licenciatura
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `competencies` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `profile_id` BIGINT UNSIGNED NOT NULL,
    `custom_id` VARCHAR(50), -- El ID visible para el usuario (Ej: COMP-01)
    `description` TEXT NOT NULL,
    `type` ENUM('disciplinar', 'sello', 'licenciatura') NOT NULL,
    -- Niveles integrados directamente (Mejora solicitada)
    `level_1` TEXT, -- Inicial
    `level_2` TEXT, -- Intermedio
    `level_3` TEXT, -- Avanzado
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`profile_id`) REFERENCES `profiles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activities` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `profile_id` BIGINT UNSIGNED NOT NULL,
    `custom_id` VARCHAR(50), -- Ej: ACT-01
    `name` VARCHAR(200) NOT NULL,
    `semester` INT NOT NULL,
    `credits` INT DEFAULT 0,
    `type` ENUM('obligatoria', 'electiva', 'optativa') DEFAULT 'obligatoria',
    FOREIGN KEY (`profile_id`) REFERENCES `profiles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relación Actividad <-> Competencia (Trazabilidad)
CREATE TABLE `activity_competency` (
    `activity_id` BIGINT UNSIGNED NOT NULL,
    `competency_id` BIGINT UNSIGNED NOT NULL,
    `contribution_level` ENUM('low', 'medium', 'high') DEFAULT 'medium',
    PRIMARY KEY (`activity_id`, `competency_id`),
    FOREIGN KEY (`activity_id`) REFERENCES `activities`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`competency_id`) REFERENCES `competencies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 3. MÓDULO SAPA (Evaluación y Acreditación)
-- ---------------------------------------------------------

-- Historial de Notas (Importadas desde CSV)
CREATE TABLE `grades` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `activity_id` BIGINT UNSIGNED NOT NULL,
    `period` VARCHAR(20) NOT NULL, -- Ej: 2025-1
    `grade` DECIMAL(3, 1) NOT NULL, -- Nota 1.0 a 7.0
    `status` ENUM('approved', 'failed') GENERATED ALWAYS AS (IF(grade >= 4.0, 'approved', 'failed')) STORED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`activity_id`) REFERENCES `activities`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Instrumentos de Evaluación (Encuestas y Casos unificados conceptualmente)
CREATE TABLE `instruments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `profile_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `type` ENUM('survey', 'case_study') NOT NULL, -- Encuesta o Caso
    `status` ENUM('draft', 'published', 'closed') DEFAULT 'draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`profile_id`) REFERENCES `profiles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Preguntas o Criterios (Para Rúbricas o Encuestas)
CREATE TABLE `criteria` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `instrument_id` BIGINT UNSIGNED NOT NULL,
    `description` TEXT NOT NULL,
    `max_score` INT DEFAULT 7, -- Nota máxima o escala
    `weight` DECIMAL(5,2) DEFAULT 1.0, -- Peso ponderado
    FOREIGN KEY (`instrument_id`) REFERENCES `instruments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Evaluaciones / Respuestas
CREATE TABLE `evaluations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `instrument_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `evaluator_id` BIGINT UNSIGNED NULL, -- NULL si es autoevaluación (encuesta)
    `final_score` DECIMAL(4,2),
    `comments` TEXT,
    `completed_at` TIMESTAMP NULL,
    `access_token` VARCHAR(64) UNIQUE NULL, -- Para acceso directo a encuestas
    FOREIGN KEY (`instrument_id`) REFERENCES `instruments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar Usuario Admin por defecto (password: admin123)
-- El hash debe generarse con password_hash() en PHP, este es un placeholder
INSERT INTO `users` (`email`, `password`, `name`, `role`) VALUES 
('admin@sapa.cl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin');

SET FOREIGN_KEY_CHECKS = 1;