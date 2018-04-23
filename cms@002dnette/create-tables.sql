CREATE TABLE header
(
  header_id INT UNSIGNED AUTO_INCREMENT
    PRIMARY KEY,
  lang      INT UNSIGNED NULL,
  page_id   INT UNSIGNED NULL,
  parent_id INT UNSIGNED NULL,
  url       TEXT         NULL,
  title     VARCHAR(60)  NULL,
  position  INT UNSIGNED NOT NULL,
  CONSTRAINT header_header_header_id_fk
  FOREIGN KEY (parent_id) REFERENCES header (header_id)
)
  ENGINE = InnoDB;

CREATE INDEX header_page_page_id_fk
  ON header (page_id);

CREATE INDEX header_header_header_id_fk
  ON header (parent_id);

CREATE TABLE language
(
  language_id     INT UNSIGNED AUTO_INCREMENT
    PRIMARY KEY,
  code            VARCHAR(5)   NOT NULL,
  site_name       VARCHAR(40)  NOT NULL,
  title_separator VARCHAR(20)  NOT NULL,
  ga              VARCHAR(15)  NOT NULL
  COMMENT 'Google Analytics',
  homepage_id     INT UNSIGNED NULL,
  errorpage_id    INT UNSIGNED NULL,
  favicon_id      INT UNSIGNED NULL,
  logo_id         INT UNSIGNED NULL,
  friendly        VARCHAR(25)  NOT NULL,
  CONSTRAINT language_language_code_uindex
  UNIQUE (code)
)
  ENGINE = InnoDB;

CREATE INDEX language_page_page_id_fk
  ON language (homepage_id);

CREATE INDEX language_page_page_id_fk_2
  ON language (errorpage_id);

CREATE INDEX language_media_media_id_fk_2
  ON language (favicon_id);

CREATE INDEX language_media_media_id_fk
  ON language (logo_id);

CREATE TABLE media
(
  media_id INT UNSIGNED AUTO_INCREMENT
    PRIMARY KEY,
  type     INT(3) UNSIGNED NOT NULL,
  lang_id  INT UNSIGNED    NOT NULL,
  name     VARCHAR(60)     NOT NULL,
  src      VARCHAR(255)    NOT NULL,
  alt      VARCHAR(255)    NULL,
  CONSTRAINT media_language_language_id_fk
  FOREIGN KEY (lang_id) REFERENCES language (language_id)
)
  ENGINE = InnoDB;

CREATE INDEX media_language_language_id_fk
  ON media (lang_id);

ALTER TABLE language
  ADD CONSTRAINT language_media_media_id_fk_2
FOREIGN KEY (favicon_id) REFERENCES media (media_id);

ALTER TABLE language
  ADD CONSTRAINT language_media_media_id_fk
FOREIGN KEY (logo_id) REFERENCES media (media_id);

CREATE TABLE page
(
  page_id       INT UNSIGNED AUTO_INCREMENT
    PRIMARY KEY,
  global_status TINYINT(1)   NULL,
  parent_id     INT UNSIGNED NULL,
  type          TINYINT(1)   NULL
  COMMENT '1 for page, 0 for post',
  CONSTRAINT page_page_page_id_fk
  FOREIGN KEY (parent_id) REFERENCES page (page_id)
)
  ENGINE = InnoDB;

CREATE INDEX page_page_page_id_fk
  ON page (parent_id);

ALTER TABLE header
  ADD CONSTRAINT header_page_page_id_fk
FOREIGN KEY (page_id) REFERENCES page (page_id);

ALTER TABLE language
  ADD CONSTRAINT language_page_page_id_fk
FOREIGN KEY (homepage_id) REFERENCES page (page_id);

ALTER TABLE language
  ADD CONSTRAINT language_page_page_id_fk_2
FOREIGN KEY (errorpage_id) REFERENCES page (page_id);

CREATE TABLE page_content_change
(
  change_id     INT UNSIGNED AUTO_INCREMENT
    PRIMARY KEY,
  page_local_id INT UNSIGNED                        NULL,
  date          TIMESTAMP DEFAULT CURRENT_TIMESTAMP NULL,
  author        INT UNSIGNED                        NOT NULL,
  pre_content   TEXT                                NOT NULL,
  after_content TEXT                                NOT NULL
)
  ENGINE = InnoDB;

CREATE INDEX page_content_change_page_local_page_local_id_fk
  ON page_content_change (page_local_id);

CREATE TABLE page_local
(
  page_local_id       INT UNSIGNED AUTO_INCREMENT
    PRIMARY KEY,
  page_id             INT UNSIGNED                            NOT NULL,
  lang_id             INT UNSIGNED                            NOT NULL,
  title               VARCHAR(60)                             NOT NULL,
  url                 VARCHAR(191)                            NOT NULL,
  description         VARCHAR(255)                            NOT NULL,
  image               INT UNSIGNED                            NOT NULL,
  video               VARCHAR(255)                            NOT NULL,
  local_status        TINYINT(1)                              NOT NULL,
  content             TEXT                                    NOT NULL,
  created             TIMESTAMP DEFAULT CURRENT_TIMESTAMP     NOT NULL,
  last_edited         TIMESTAMP DEFAULT '0000-00-00 00:00:00' NOT NULL,
  author              INT UNSIGNED                            NOT NULL
  COMMENT 'user_id
		',
  display_title       TINYINT                                 NOT NULL,
  display_breadcrumbs TINYINT                                 NOT NULL,
  CONSTRAINT `page_id-lang`
  UNIQUE (page_id, lang_id),
  CONSTRAINT page_local_lang_url
  UNIQUE (lang_id, url),
  CONSTRAINT page_local_page
  FOREIGN KEY (page_id) REFERENCES page (page_id),
  CONSTRAINT page_local_language_language_id_fk
  FOREIGN KEY (lang_id) REFERENCES language (language_id),
  FULLTEXT content(content, title, url, description)
)
  ENGINE = InnoDB;

CREATE INDEX content
  ON page_local (content, title, url, description);

CREATE INDEX page_local_user_user_id_fk
  ON page_local (author);

ALTER TABLE page_content_change
  ADD CONSTRAINT page_content_change_page_local_page_local_id_fk
FOREIGN KEY (page_local_id) REFERENCES page_local (page_local_id);

CREATE TABLE settings
(
  settings_id INT UNSIGNED AUTO_INCREMENT
    PRIMARY KEY,
  `option`    VARCHAR(60) NOT NULL,
  value       TEXT        NOT NULL,
  CONSTRAINT settings_settings_lang_option
  UNIQUE (`option`)
)
  ENGINE = InnoDB;

CREATE TABLE slide
(
  slide_id  INT UNSIGNED AUTO_INCREMENT
    PRIMARY KEY,
  slider_id INT UNSIGNED NOT NULL,
  title     VARCHAR(60)  NOT NULL,
  position  INT UNSIGNED NOT NULL,
  content   TEXT         NOT NULL
)
  ENGINE = InnoDB;

CREATE INDEX slide_slider_slider_id_fk
  ON slide (slider_id);

CREATE TABLE slider
(
  slider_id INT UNSIGNED AUTO_INCREMENT
    PRIMARY KEY,
  title     VARCHAR(60)  NOT NULL,
  lang_id   INT UNSIGNED NULL,
  CONSTRAINT slider_language_language_id_fk
  FOREIGN KEY (lang_id) REFERENCES language (language_id)
)
  ENGINE = InnoDB;

CREATE INDEX slider_language_language_id_fk
  ON slider (lang_id);

ALTER TABLE slide
  ADD CONSTRAINT slide_slider_slider_id_fk
FOREIGN KEY (slider_id) REFERENCES slider (slider_id);

CREATE TABLE tag_local
(
  tag_local_id INT UNSIGNED AUTO_INCREMENT
    PRIMARY KEY,
  tag_id       INT UNSIGNED NULL,
  lang_id      INT UNSIGNED NULL,
  name         VARCHAR(30)  NOT NULL,
  CONSTRAINT tag_local_tag_id_uindex
  UNIQUE (tag_id, lang_id),
  CONSTRAINT tag_local_name_uindex
  UNIQUE (name),
  CONSTRAINT tag_local_language_language_id_fk
  FOREIGN KEY (lang_id) REFERENCES language (language_id)
)
  ENGINE = InnoDB;

CREATE INDEX tag_local_language_language_id_fk
  ON tag_local (lang_id);

CREATE TABLE tag_page
(
  tag_page_id INT UNSIGNED AUTO_INCREMENT
    PRIMARY KEY,
  tag_id      INT UNSIGNED NULL,
  page_id     INT UNSIGNED NULL,
  CONSTRAINT tag_page_page_id_uindex
  UNIQUE (page_id, tag_id),
  CONSTRAINT tag_page_tag_local_tag_id_fk
  FOREIGN KEY (tag_id) REFERENCES tag_local (tag_id),
  CONSTRAINT tag_page_page_page_id_fk
  FOREIGN KEY (page_id) REFERENCES page (page_id)
)
  ENGINE = InnoDB;

CREATE INDEX tag_page_tag_local_tag_id_fk
  ON tag_page (tag_id);

CREATE TABLE user
(
  user_id          INT UNSIGNED AUTO_INCREMENT
    PRIMARY KEY,
  username         VARCHAR(40)                         NOT NULL,
  email            VARCHAR(100)                        NOT NULL,
  password         VARCHAR(255)                        NOT NULL,
  created          TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
  verified         TIMESTAMP                           NULL,
  role             INT(1) UNSIGNED                     NOT NULL,
  current_language VARCHAR(5)                          NULL,
  CONSTRAINT username
  UNIQUE (username)
)
  ENGINE = InnoDB;

ALTER TABLE page_local
  ADD CONSTRAINT page_local_user_user_id_fk
FOREIGN KEY (author) REFERENCES user (user_id);

