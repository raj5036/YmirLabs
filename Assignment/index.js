const express = require('express');
const {validate} = require('express-validation');

const { adminRoutes, clientRoutes } = require('./src/routes/routes');
const { generateDiscountCodeController, generateStoreSummaryController } = require('./src/controllers/adminController');
const { addToCartController, checkoutController } = require('./src/controllers/clientController');
const { generateDiscountCodeValidator, addToCartValidator, checkoutValidator } = require('./src/middlewares/validators');
const { ValidationError } = require('express-validation');

const app = express();
const PORT = 3001 || process.env.PORT;

app.use(express.json());

// Server health checkup
app.get(adminRoutes.SERVER_HEALTH_CHECKUP, (req, res) => {
	res.send('<h1>Server is running properly</h1>');
});

// Admin API to generate Discount code
app.post(
	adminRoutes.GENERATE_DISCOUNT_CODE,
	validate(generateDiscountCodeValidator),
	generateDiscountCodeController
);

// Admin API to generate Store summary
app.get(
	adminRoutes.GENERATE_STORE_SUMMARY,
	generateStoreSummaryController
);

// Client API to add items to Cart
app.post(
	clientRoutes.ADD_TO_CART,
	validate(addToCartValidator),
	addToCartController
);

// Client API for checking out
app.post(
	clientRoutes.CHECKOUT,
	validate(checkoutValidator),
	checkoutController
);

// Catch Validation error
app.use(function(err, req, res, next) {
	if (err instanceof ValidationError) {
		return res.status(err.statusCode).json(err)
	}

	return res.status(400).json(err)
});

app.listen(PORT, () => {
	console.log(`Server is running on Port: ${PORT}`);
});