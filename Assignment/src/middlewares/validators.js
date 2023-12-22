const joi = require('joi');
const validate = require('express-validation');

exports.generateDiscountCodeValidator = {
	body: joi.object().keys({
		discountCode: joi.string().required()
	})
};

exports.generateStoreSummaryValidator = {};

exports.addToCartValidator = {
	body: joi.object().keys({
		itemId: joi.string().required(),
		userId: joi.string().required(),
		count: joi.number().required()
	})
};

exports.checkoutValidator = {
	body: joi.object().keys({
		userId: joi.string().required(),
		discountCode: joi.string().optional()
	})
};