# Create the release_ratings table

DROP TABLE IF EXISTS release_ratings;
CREATE TABLE release_ratings (
  releases_id MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'FK to releases.id',
  video   MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Users video score.',
  audio MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Users audio score.',
  voteup     MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Number of upvotes',
  votedown     MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Number of downvotes',
  passworded     MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Number of times release has been reported as passworded',
  spam     MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Number of times release is reported as spam',
  server    VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Reported usenet provider',
  PRIMARY KEY          (releases_id)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;