CREATE TABLE users (
	id VARCHAR(255) 
	   PRIMARY KEY 
	   NOT NULL 
	   UNIQUE,

	name TEXT,

	balance INTEGER 
	        NOT NULL 
	         DEFAULT 0
);

CREATE TABLE products (
	id VARCHAR(255) 
	   PRIMARY KEY 
	   NOT NULL 
	   UNIQUE,

	name TEXT,

	cost INTEGER 
	     NOT NULL
);

CREATE TABLE lastPurchase (
	id VARCHAR(255) 
	   PRIMARY KEY 
	   NOT NULL 
	   UNIQUE 
	   REFERENCES users (id),

	amount INTEGER,

	CHECK (amount >= 0)
);

