SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

CREATE SCHEMA IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `mydb` ;

-- -----------------------------------------------------
-- Table `mydb`.`CRDevice`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CRDevice` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `appID` VARCHAR(255) NOT NULL,
  `appName` VARCHAR(255) NOT NULL,
  `appVersion` VARCHAR(45) NOT NULL,
  `badge_count` INT NOT NULL DEFAULT 0,
  `device_vendor_id` VARCHAR(255) NULL,
  `push_token` VARCHAR(255) NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `device_vendor_id_UNIQUE` (`device_vendor_id` ASC),
  INDEX `push_idx` (`push_token` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`CRUser`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CRUser` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(45) NULL,
  `email` VARCHAR(255) NOT NULL,
  `facebook_id` VARCHAR(45) NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `photo_url` VARCHAR(255) NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `email_idx` (`email` ASC),
  INDEX `facebook_id_idx` (`facebook_id` ASC),
  INDEX `username_idx` (`username` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`CRDeviceUser`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CRDeviceUser` (
  `id` VARCHAR(45) NULL,
  `device_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  INDEX `fk_CRUserDevices_CRDevice_idx` (`device_id` ASC),
  INDEX `fk_CRUserDevices_CRUser1_idx` (`user_id` ASC),
  CONSTRAINT `fk_CRUserDevices_CRDevice`
    FOREIGN KEY (`device_id`)
    REFERENCES `mydb`.`CRDevice` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_CRUserDevices_CRUser1`
    FOREIGN KEY (`user_id`)
    REFERENCES `mydb`.`CRUser` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`CRFriends`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CRFriends` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `friend_id` INT UNSIGNED NOT NULL,
  `ignore` TINYINT(1) NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_CRFriends_CRUser1_idx` (`user_id` ASC),
  INDEX `fk_CRFriends_CRUser2_idx` (`friend_id` ASC),
  CONSTRAINT `fk_CRFriends_CRUser1`
    FOREIGN KEY (`user_id`)
    REFERENCES `mydb`.`CRUser` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_CRFriends_CRUser2`
    FOREIGN KEY (`friend_id`)
    REFERENCES `mydb`.`CRUser` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`CRMovie`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CRMovie` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rotten_tomatoes_id` VARCHAR(45) NOT NULL,
  `itunes_id` VARCHAR(45) NULL,
  `imdb_id` VARCHAR(45) NULL,
  `tmdb_id` VARCHAR(45) NULL,
  `tms_root_id` VARCHAR(45) NULL,
  `tms_movie_id` VARCHAR(45) NULL,
  `hashtag` VARCHAR(45) NOT NULL,
  `title` VARCHAR(255) NULL,
  `box_office_release_date` DATETIME NULL,
  `dvd_release_date` DATETIME NULL,
  `tmdb_poster_path` VARCHAR(255) NULL,
  `priority` INT NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`CRRating`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CRRating` (
  `int` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `movie_id` INT UNSIGNED NOT NULL,
  `notified_box_office` TINYINT(1) NOT NULL DEFAULT 0,
  `notified_dvd` TINYINT(1) NOT NULL DEFAULT 0,
  `rating` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`int`),
  INDEX `fk_CRRating_CRUser1_idx` (`user_id` ASC),
  INDEX `fk_CRRating_CRMovie1_idx` (`movie_id` ASC),
  CONSTRAINT `fk_CRRating_CRUser1`
    FOREIGN KEY (`user_id`)
    REFERENCES `mydb`.`CRUser` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_CRRating_CRMovie1`
    FOREIGN KEY (`movie_id`)
    REFERENCES `mydb`.`CRMovie` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`CRNotification`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CRNotification` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `notification_type` VARCHAR(45) NOT NULL,
  `from_user_id` INT UNSIGNED NOT NULL,
  `to_user_id` INT UNSIGNED NOT NULL,
  `rating_id` INT UNSIGNED NULL,
  `is_viewed` TINYINT(1) NOT NULL DEFAULT 0,
  `message` VARCHAR(255) NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_CRNotification_CRUser1_idx` (`from_user_id` ASC),
  INDEX `fk_CRNotification_CRUser2_idx` (`to_user_id` ASC),
  INDEX `fk_CRNotification_CRRating1_idx` (`rating_id` ASC),
  CONSTRAINT `fk_CRNotification_CRUser1`
    FOREIGN KEY (`from_user_id`)
    REFERENCES `mydb`.`CRUser` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_CRNotification_CRUser2`
    FOREIGN KEY (`to_user_id`)
    REFERENCES `mydb`.`CRUser` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_CRNotification_CRRating1`
    FOREIGN KEY (`rating_id`)
    REFERENCES `mydb`.`CRRating` (`int`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`CRPushNotification`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CRPushNotification` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `device_id` INT UNSIGNED NOT NULL,
  `notification_id` INT UNSIGNED NULL,
  `badge` INT NOT NULL DEFAULT 0,
  `message` VARCHAR(255) NULL,
  `sound` VARCHAR(45) NULL,
  `extra_params` VARCHAR(255) NULL,
  `sent` TINYINT(1) NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_CRPushNotification_CRDevice1_idx` (`device_id` ASC),
  INDEX `fk_CRPushNotification_CRNotification1_idx` (`notification_id` ASC),
  CONSTRAINT `fk_CRPushNotification_CRDevice1`
    FOREIGN KEY (`device_id`)
    REFERENCES `mydb`.`CRDevice` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_CRPushNotification_CRNotification1`
    FOREIGN KEY (`notification_id`)
    REFERENCES `mydb`.`CRNotification` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
