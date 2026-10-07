CREATE TABLE IF NOT EXISTS "sessions" (
  "id" varchar NOT NULL PRIMARY KEY,
  "user_id" integer,
  "ip_address" varchar(45),
  "user_agent" text,
  "payload" text NOT NULL,
  "last_activity" integer NOT NULL
);

CREATE INDEX IF NOT EXISTS "sessions_user_id_index" ON "sessions" ("user_id");
CREATE INDEX IF NOT EXISTS "sessions_last_activity_index" ON "sessions" ("last_activity");
