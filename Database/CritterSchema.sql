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
-- Table `mydb`.`CRAnalytics`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CRAnalytics` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `device_id` INT UNSIGNED NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `subject_id` INT(11) NULL DEFAULT NULL,
  `subject_type` VARCHAR(255) NULL DEFAULT NULL,
  `event` VARCHAR(255) NOT NULL,
  `event_id` INT(11) NULL DEFAULT NULL,
  `event_type` VARCHAR(255) NULL DEFAULT NULL,
  `object` VARCHAR(255) NOT NULL,
  `object_id` INT(11) NULL DEFAULT NULL,
  `object_type` VARCHAR(255) NULL DEFAULT NULL,
  `device` VARCHAR(255) NOT NULL,
  `params` TEXT NULL DEFAULT NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_CRAnalytics_CRDevice1_idx` (`device_id` ASC),
  CONSTRAINT `fk_CRAnalytics_CRDevice1`
    FOREIGN KEY (`device_id`)
    REFERENCES `mydb`.`CRDevice` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 146
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `mydb`.`CRUser`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CRUser` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `password_hash` VARCHAR(255) NULL,
  `email` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `facebook_id` VARCHAR(45) NULL,
  `facebook_username` VARCHAR(255) NULL,
  `photo_url` VARCHAR(255) NULL,
  `tutorials_shown` TINYINT(1) NOT NULL DEFAULT 0,
  `push_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `push_watchlist_enabled` VARCHAR(45) NOT NULL DEFAULT '1',
  `birthday` DATETIME NULL,
  `gender` VARCHAR(1) NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `email_idx` (`email` ASC),
  INDEX `facebook_id_idx` (`facebook_id` ASC),
  INDEX `username_idx` (`name` ASC),
  UNIQUE INDEX `email_UNIQUE` (`email` ASC),
  UNIQUE INDEX `facebook_id_UNIQUE` (`facebook_id` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`CRDeviceUser`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CRDeviceUser` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `device_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  INDEX `fk_CRUserDevices_CRDevice_idx` (`device_id` ASC),
  INDEX `fk_CRUserDevices_CRUser1_idx` (`user_id` ASC),
  PRIMARY KEY (`id`),
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
-- Table `mydb`.`CREmailToken`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CREmailToken` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(100) NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT '0',
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_CREmailToken_CRUser1_idx` (`user_id` ASC),
  CONSTRAINT `fk_CREmailToken_CRUser1`
    FOREIGN KEY (`user_id`)
    REFERENCES `mydb`.`CRUser` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 16
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


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
  `youtube_trailer_id` VARCHAR(45) NULL,
  `priority` INT NULL,
  `critter_rating` INT NOT NULL DEFAULT 0,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `rotten_tomatoes_id_UNIQUE` (`rotten_tomatoes_id` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`CRRating`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CRRating` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `movie_id` INT UNSIGNED NOT NULL,
  `notified_box_office` TINYINT(1) NOT NULL DEFAULT 0,
  `notified_dvd` TINYINT(1) NOT NULL DEFAULT 0,
  `rating` INT NOT NULL DEFAULT 0,
  `super` TINYINT(1) NOT NULL DEFAULT 0,
  `comments` TEXT NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_CRRating_CRUser1_idx` (`user_id` ASC),
  INDEX `fk_CRRating_CRMovie1_idx` (`movie_id` ASC),
  UNIQUE INDEX `uniqueratingsbyuser_idx` (`user_id` ASC, `movie_id` ASC),
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
    REFERENCES `mydb`.`CRRating` (`id`)
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


-- -----------------------------------------------------
-- Table `mydb`.`CRNetflix`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CRNetflix` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `movie_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NULL,
  `release_year` YEAR NULL,
  `season` INT NULL DEFAULT 0,
  `netflix_id` VARCHAR(100) NULL,
  `avail_us` TINYINT NULL DEFAULT 0,
  `avail_ca` TINYINT NULL DEFAULT 0,
  `avail_uk` TINYINT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `fk_CRNetflix_CRMovie1_idx` (`movie_id` ASC),
  CONSTRAINT `fk_CRNetflix_CRMovie1`
    FOREIGN KEY (`movie_id`)
    REFERENCES `mydb`.`CRMovie` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`CRGenre`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CRGenre` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  INDEX `genreNameIDX` (`name` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`CRGenreMovie`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CRGenreMovie` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `movie_id` INT UNSIGNED NOT NULL,
  `genre_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_CRGenreMovie_CRMovie1_idx` (`movie_id` ASC),
  INDEX `fk_CRGenreMovie_CRGenre1_idx` (`genre_id` ASC),
  CONSTRAINT `fk_CRGenreMovie_CRMovie1`
    FOREIGN KEY (`movie_id`)
    REFERENCES `mydb`.`CRMovie` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_CRGenreMovie_CRGenre1`
    FOREIGN KEY (`genre_id`)
    REFERENCES `mydb`.`CRGenre` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
