# Create the release_ratings table

DROP TABLE IF EXISTS release_ratings;
CREATE TABLE release_ratings (
  releases_id MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'FK to releases.id',
  audio TINYINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Users audio score.',
  audiocnt   TINYINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Number of users audio votes.',
  video   TINYINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Users video score.',
  videocnt   TINYINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Numbert of users video votes.',
  voteup     MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Number of upvotes',
  votedown     MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Number of downvotes',
  passworded     MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Number of times release has been reported as passworded',
  spam     MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Number of times release is reported as spam',
  PRIMARY KEY          (releases_id)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
