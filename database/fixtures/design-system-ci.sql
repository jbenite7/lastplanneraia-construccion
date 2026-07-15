CREATE TABLE general_usuarios (
  id INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  cargo VARCHAR(100) NOT NULL,
  usuario VARCHAR(20) NOT NULL,
  password VARCHAR(200) NOT NULL,
  force_password_change TINYINT(1) NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY unique_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE project_members (
  id INT NOT NULL AUTO_INCREMENT,
  project_id INT NOT NULL,
  user_id INT NOT NULL,
  role VARCHAR(50) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_project_user (project_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE general_proyectos_procesos (
  Id INT NOT NULL AUTO_INCREMENT,
  Proyecto_Proceso VARCHAR(50) NOT NULL,
  Base_de_Datos VARCHAR(30) NOT NULL,
  Area VARCHAR(50) NOT NULL,
  Activo INT NOT NULL DEFAULT 1,
  Acceso INT NOT NULL DEFAULT 1,
  pdcActivo INT NOT NULL DEFAULT 0,
  PRIMARY KEY (Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE general_auditoria_acciones (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario VARCHAR(100) NOT NULL,
  id_sesion VARCHAR(128) NOT NULL,
  modulo VARCHAR(100) NOT NULL,
  accion VARCHAR(50) NOT NULL,
  descripcion TEXT NULL,
  ip_address VARCHAR(45) NULL,
  proyecto VARCHAR(100) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE system_notifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  type VARCHAR(50) NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  item_count INT NOT NULL DEFAULT 1,
  project_id INT NULL,
  is_read TINYINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY unread_user (user_id, is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE general_feature_flags (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  flag_key VARCHAR(100) NOT NULL,
  flag_value TINYINT NOT NULL DEFAULT 0,
  description VARCHAR(255) NULL,
  updated_by VARCHAR(100) NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_flag_key (flag_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO general_usuarios
  (id, nombre, email, cargo, usuario, password, force_password_change, activo)
VALUES
  (1, 'Test Admin', 'admin@ci.invalid', 'Administrador', 'test.A',
   '$2y$10$vdbXz3NfKDv5Ctyr/ijIVOk9uhCsjNC1dMG3MxMQmBJr/yCLlueJO', 0, 1),
  (2, 'Test Residente', 'resident@ci.invalid', 'Residente de Obra', 'test.R',
   '$2y$10$vdbXz3NfKDv5Ctyr/ijIVOk9uhCsjNC1dMG3MxMQmBJr/yCLlueJO', 0, 1),
  (3, 'Test Subcontratista', 'contractor@ci.invalid', 'Subcontratista', 'test.C',
   '$2y$10$vdbXz3NfKDv5Ctyr/ijIVOk9uhCsjNC1dMG3MxMQmBJr/yCLlueJO', 0, 1),
  (4, 'Test Visualizador', 'viewer@ci.invalid', 'Visualizador', 'test.V',
   '$2y$10$vdbXz3NfKDv5Ctyr/ijIVOk9uhCsjNC1dMG3MxMQmBJr/yCLlueJO', 0, 1);

INSERT INTO general_proyectos_procesos
  (Id, Proyecto_Proceso, Base_de_Datos, Area, Activo, Acceso, pdcActivo)
VALUES (73, 'Da Porto', 'da_porto', 'Construccion', 1, 1, 1);

INSERT INTO semanas_activas
  (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem)
VALUES (73, 1, 1, '2026-07-06', '2026-07-12');

INSERT INTO programa
  (project_id, unique_id, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin,
   Ruta_Critica, Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones)
VALUES
  (73, 101, 1, '1.1', 'Actividad CI editable', 0, '2026-07-06', '2026-07-12',
   0, 0, 'Actividad Futura', 1, 0);

INSERT INTO programa_consolidado
  (project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa, Id,
   Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, Ejecutado, Estado,
   Semanas_Inicio, Estado_Restricciones, Activa, cantidad_ppto, unidad)
VALUES
  (73, 1, 1, 1, 101, 1, '1.1', 'Actividad CI editable', 0, '2026-07-06',
   '2026-07-12', 0, 0, 'Actividad Futura', 1, 0, 1, 100, 'ml');

INSERT INTO project_members (project_id, user_id, role)
VALUES (73, 1, 'A'), (73, 2, 'R'), (73, 3, 'C'), (73, 4, 'V');
