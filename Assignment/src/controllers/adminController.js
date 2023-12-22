const { DiscountCodes } = require("../database");
const adminServices = require('../services/adminServices');


exports.generateDiscountCodeController = (req, res) => {
	const { discountCode } = req.body;
	return Promise.resolve(
		adminServices.generateDiscountCode(discountCode)
	)
	.then((data) => {
		console.log('Successfully added Discount Code to Database');
		return res.status(201).json({'code': 'SUCCESS', data});
	})
	.catch(err => {
		if (err.code === 'DUPLICATE_DISCOUNT_CODE') {
			return res.status(err.statusCode).json({'code': 'FAILED', 'data': err});
		}
		return res.status(500).json({'code': 'FAILED', 'data': err});
	})
};

exports.generateStoreSummaryController = (req, res) => {
	return Promise.resolve(
		adminServices.generateStoreSummary()
	)
	.then(data => {
		console.log('Successfully fetched store summary', data);
		res.status(200).json({'code': 'SUCCESS', data});
	})
	.catch(err => {
		return res.status(500).json({'code': 'FAILED', 'data': err});
	});
};