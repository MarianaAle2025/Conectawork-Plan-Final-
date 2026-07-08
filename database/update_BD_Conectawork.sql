-- ========================================================
-- SCRIPT DE ACTUALIZACIÓN DE BASE DE DATOS - CONECTAWORK
-- Módulo de Reporte de Actividades y CRUD de Empleados
-- ========================================================

USE `bd_conectawork`;

-- 1. Tabla de Empresas (Clientes o Entidades)
CREATE TABLE IF NOT EXISTS `empresas` (
  `id_empresa` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_empresa`),
  UNIQUE INDEX `nombre_UNIQUE` (`nombre` ASC)
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 2. Tabla de Proyectos
CREATE TABLE IF NOT EXISTS `proyectos` (
  `id_proyecto` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `id_empresa` INT NOT NULL,
  PRIMARY KEY (`id_proyecto`),
  INDEX `fk_proyecto_empresa_idx` (`id_empresa` ASC),
  CONSTRAINT `fk_proyecto_empresa`
    FOREIGN KEY (`id_empresa`)
    REFERENCES `empresas` (`id_empresa`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 3. Tabla de Catálogo de Actividades
CREATE TABLE IF NOT EXISTS `actividades` (
  `id_actividad` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id_actividad`),
  UNIQUE INDEX `act_nombre_UNIQUE` (`nombre` ASC)
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- 4. Modificaciones en la Tabla de Empleados
-- Primero, verificamos si las columnas existen para evitar errores al re-ejecutar.
-- Agregamos id_empresa e id_jefe si no existen.
SET @dbname = DATABASE();
SET @tablename = 'empleado';

SET @columnname = 'id_empresa';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  'SELECT 1',
  'ALTER TABLE empleado ADD COLUMN id_empresa INT NULL AFTER modalidad_trabajo, ADD CONSTRAINT fk_empleado_empresa FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa) ON DELETE SET NULL ON UPDATE NO ACTION'
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = 'id_jefe';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  'SELECT 1',
  'ALTER TABLE empleado ADD COLUMN id_jefe INT NULL AFTER id_empresa, ADD CONSTRAINT fk_empleado_jefe FOREIGN KEY (id_jefe) REFERENCES empleado(id_empleado) ON DELETE SET NULL ON UPDATE NO ACTION'
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Tabla de Reporte de Actividades
CREATE TABLE IF NOT EXISTS `reportes_actividades` (
  `id_reporte` INT NOT NULL AUTO_INCREMENT,
  `id_empleado` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_fin` TIME NOT NULL,
  `tiempo_total_minutos` INT NOT NULL,
  `id_actividad` INT NOT NULL,
  `id_proyecto` INT NOT NULL,
  `id_empresa` INT NOT NULL,
  `id_jefe` INT NULL,
  `descripcion` TEXT NOT NULL,
  `estado` VARCHAR(20) NOT NULL DEFAULT 'Finalizado',
  PRIMARY KEY (`id_reporte`),
  INDEX `fk_reporte_empleado_idx` (`id_empleado` ASC),
  INDEX `fk_reporte_actividad_idx` (`id_actividad` ASC),
  INDEX `fk_reporte_proyecto_idx` (`id_proyecto` ASC),
  INDEX `fk_reporte_empresa_idx` (`id_empresa` ASC),
  INDEX `fk_reporte_jefe_idx` (`id_jefe` ASC),
  CONSTRAINT `fk_reporte_empleado`
    FOREIGN KEY (`id_empleado`)
    REFERENCES `empleado` (`id_empleado`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_reporte_actividad`
    FOREIGN KEY (`id_actividad`)
    REFERENCES `actividades` (`id_actividad`)
    ON DELETE RESTRICT
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_reporte_proyecto`
    FOREIGN KEY (`id_proyecto`)
    REFERENCES `proyectos` (`id_proyecto`)
    ON DELETE RESTRICT
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_reporte_empresa`
    FOREIGN KEY (`id_empresa`)
    REFERENCES `empresas` (`id_empresa`)
    ON DELETE RESTRICT
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_reporte_jefe`
    FOREIGN KEY (`id_jefe`)
    REFERENCES `empleado` (`id_empleado`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- ========================================================
-- INSERCIÓN DE DATOS SEMILLA (SEED DATA)
-- ========================================================

-- Insertar Actividades por defecto
INSERT IGNORE INTO `actividades` (`nombre`) VALUES
('Desarrollo'),
('Análisis'),
('Diseño'),
('Estimación'),
('Corrección de Bugs'),
('Testing'),
('Documentación'),
('Soporte'),
('Revisión de código'),
('Reunión'),
('Capacitación'),
('Investigación'),
('Planeación'),
('Deploy'),
('Reporte'),
('Otro');

-- Insertar Empresas por defecto
INSERT IGNORE INTO `empresas` (`id_empresa`, `nombre`) VALUES
(1, 'Desarrollo Interno'),
(2, 'Cliente Alpha'),
(3, 'Cliente Beta');

-- Insertar Proyectos por defecto
INSERT IGNORE INTO `proyectos` (`id_proyecto`, `nombre`, `id_empresa`) VALUES
(1, 'ConectaWork', 1),
(2, 'Proyecto Alpha', 2),
(3, 'Proyecto Beta', 3);

-- Actualizar contraseñas existentes a hashes cifrados de password_hash()
-- Contraseña 'admin1234' para Alejandra (Administrador)
UPDATE `usuario` SET `contraseña` = '$2y$10$5pBFpM5d3Xq7MVil20D13OsL1ze3an4ForVzYmPSrZt5mwzyZegBW' WHERE `id_usuario` = 1;
-- Contraseña 'user1234' para Juan Pérez (Empleado)
UPDATE `usuario` SET `contraseña` = '$2y$10$2nO1d2OctWHU86y3DFE0XuwhBIY0ejtShhjuyJNfLxeRYQkgfiGTS' WHERE `id_usuario` = 2;

-- Actualizar empresas y jefes inmediatos para registros existentes
UPDATE `empleado` SET `id_empresa` = 1 WHERE `id_empresa` IS NULL;
UPDATE `empleado` SET `id_jefe` = 1 WHERE `id_empleado` = 2 AND `id_jefe` IS NULL;
