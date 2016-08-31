# Create the users_release_ratings table

DROP TABLE IF EXISTS users_release_ratings;
CREATE TABLE users_release_ratings (
  releases_id MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'FK to releases.id',
  users_id MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'FK to users.id',
  audio TINYINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Users audio score.',
  video   TINYINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Users video score.',
  voteup    VARCHAR(128) NOT NULL DEFAULT '' COMMENT 'Vote up release',
  votedown    VARCHAR(128) NOT NULL DEFAULT '' COMMENT 'Vote down release',
  passworded     MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Is release marked as passworded by user?',
  spam     MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Is release marked as spam by user?',
  server    VARCHAR(128) NOT NULL DEFAULT '' COMMENT 'USP of the reported release',
  PRIMARY KEY          (releases_id)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
