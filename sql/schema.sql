CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'mentee' CHECK (role IN ('mentee', 'mentor', 'admin', 'super_admin')),
  full_name VARCHAR(120) NOT NULL,
  gender VARCHAR(40) DEFAULT NULL,
  birthdate DATE DEFAULT NULL,
  mentor_id INTEGER DEFAULT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'paused')),
  mfa_code_hash VARCHAR(255) DEFAULT NULL,
  email_verified_at TIMESTAMP NULL,
  email_verify_hash VARCHAR(64) DEFAULT NULL,
  email_verify_expires TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at TIMESTAMP NULL,
  CONSTRAINT fk_users_mentor FOREIGN KEY (mentor_id) REFERENCES users (id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS game_progress (
  user_id INTEGER NOT NULL PRIMARY KEY,
  mhm SMALLINT NOT NULL DEFAULT 50,
  quest_id VARCHAR(80) DEFAULT NULL,
  map_x INTEGER NOT NULL DEFAULT 0,
  map_y INTEGER NOT NULL DEFAULT 0,
  completed SMALLINT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_progress_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS assessments (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  type VARCHAR(10) NOT NULL CHECK (type IN ('pre', 'post')),
  answers JSONB NOT NULL,
  mean_score DECIMAL(4,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_assess_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS abilities (
  id SERIAL PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS user_abilities (
  user_id INTEGER NOT NULL,
  ability_id INTEGER NOT NULL,
  earned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, ability_id),
  CONSTRAINT fk_ua_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_ua_ability FOREIGN KEY (ability_id) REFERENCES abilities (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS calls (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  mentor_id INTEGER NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'active', 'ended')),
  language VARCHAR(20) NOT NULL DEFAULT 'english' CHECK (language IN ('english', 'filipino', 'taglish')),
  started_at TIMESTAMP NULL,
  mentor_joined_at TIMESTAMP NULL,
  ended_at TIMESTAMP NULL,
  duration_seconds INTEGER NOT NULL DEFAULT 0,
  response_seconds INTEGER NOT NULL DEFAULT 0,
  mhm_at_start SMALLINT DEFAULT NULL,
  mhm_at_end SMALLINT DEFAULT NULL,
  flagged SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_call_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_call_mentor FOREIGN KEY (mentor_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS call_lines (
  id SERIAL PRIMARY KEY,
  call_id INTEGER NOT NULL,
  speaker_id INTEGER NOT NULL,
  speaker_role VARCHAR(20) NOT NULL CHECK (speaker_role IN ('mentee', 'mentor')),
  line_text TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_line_call FOREIGN KEY (call_id) REFERENCES calls (id) ON DELETE CASCADE,
  CONSTRAINT fk_line_speaker FOREIGN KEY (speaker_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS call_transcriptions (
  id SERIAL PRIMARY KEY,
  call_id INTEGER NOT NULL UNIQUE,
  user_id INTEGER NOT NULL,
  mentor_id INTEGER NOT NULL,
  transcript_text TEXT NOT NULL,
  language VARCHAR(20) NOT NULL DEFAULT 'english' CHECK (language IN ('english', 'filipino', 'taglish')),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tr_call FOREIGN KEY (call_id) REFERENCES calls (id) ON DELETE CASCADE,
  CONSTRAINT fk_tr_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_tr_mentor FOREIGN KEY (mentor_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS audit_logs (
  id SERIAL PRIMARY KEY,
  actor_id INTEGER DEFAULT NULL,
  action VARCHAR(120) NOT NULL,
  target_type VARCHAR(80) DEFAULT NULL,
  target_id INTEGER DEFAULT NULL,
  reason VARCHAR(255) DEFAULT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_actor FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE SET NULL
);
