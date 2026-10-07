CREATE TABLE IF NOT EXISTS users (
 id BIGSERIAL PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(255) UNIQUE NOT NULL,
 password_hash TEXT NOT NULL,
 role VARCHAR(20) NOT NULL DEFAULT 'user' CHECK (role IN ('user','admin')),
 status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','suspended')),
 created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
 last_login_at TIMESTAMPTZ
);

CREATE TABLE IF NOT EXISTS accounts (
 id BIGSERIAL PRIMARY KEY,
 user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
 currency VARCHAR(10) NOT NULL DEFAULT 'USD',
 balance NUMERIC(18,2) NOT NULL DEFAULT 0,
 created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
 UNIQUE(user_id,currency)
);

CREATE TABLE IF NOT EXISTS transactions (
 id BIGSERIAL PRIMARY KEY,
 user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
 type VARCHAR(20) NOT NULL CHECK (type IN ('deposit','withdrawal','purchase','transfer')),
 description VARCHAR(180) NOT NULL,
 amount NUMERIC(18,2) NOT NULL,
 currency VARCHAR(10) NOT NULL DEFAULT 'USD',
 status VARCHAR(20) NOT NULL DEFAULT 'completed' CHECK (status IN ('pending','completed','failed')),
 created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS login_attempts (
 id BIGSERIAL PRIMARY KEY,
 email VARCHAR(255) NOT NULL,
 ip_hash CHAR(64) NOT NULL,
 attempted_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
 successful BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE INDEX IF NOT EXISTS idx_transactions_user_created ON transactions(user_id,created_at DESC);
CREATE INDEX IF NOT EXISTS idx_login_attempts_email_time ON login_attempts(email,attempted_at DESC);
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip_time ON login_attempts(ip_hash,attempted_at DESC);
