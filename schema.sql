CREATE TABLE users (
	-- the id/barcode of a user
	id VARCHAR(255) 
	   PRIMARY KEY 
	   NOT NULL 
	   UNIQUE,

	-- the user's name, can be null
	name TEXT,

	-- the user's balance, must not be null
	balance INTEGER 
	        NOT NULL 
	         DEFAULT 0,

	-- the amout of consumed calories
	calories INTEGER
		DEFAULT 0,

	-- whether the user counts calories
	countCalories BOOLEAN
		DEFAULT false
);

CREATE TABLE products (
	-- the barcode of the product
	id VARCHAR(255) 
	   PRIMARY KEY 
	   NOT NULL 
	   UNIQUE,

	-- the name of a product, must be set
	name TEXT
	     NOT NULL,

	-- the cost of a product, must be set
	cost INTEGER 
	     NOT NULL,

	-- the amount of calories in the product
	calories INTEGER
	     DEFAULT 0
);

CREATE TABLE lastPurchase (
	-- the id if the user who did this purchase
	id VARCHAR(255) 
	   PRIMARY KEY 
	   NOT NULL 
	   UNIQUE 
	   REFERENCES users (id),

	-- the amount the user purchased for
	amount INTEGER,

	-- one should not be able to loose balance by undoing a purchase
	CHECK (amount >= 0)
);

