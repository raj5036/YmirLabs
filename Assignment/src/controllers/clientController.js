const clientServices = require('../services/clientServices');

exports.addToCartController = (req, res) => {
	const {itemId, userId, count} = req. body;
	return Promise.resolve(clientServices.addToCartService(itemId, userId, count))
		.then(data => {
			return res.status(201).json({'code': 'SUCCESS', data});
		})
		.catch(err => {
			console.log('Something went wrong in addToCartController', err);
			if (
				err.code === 'ITEM_NOT_FOUND' || 
				err.code === 'ITEM_ALREADY_IN_CART' || 
				err.code === 'ITEM_COUNT_SHORTAGE'
			) {
				return res.status(err.statusCode).json({'code': 'FAILED', 'data': err});
			}
			return res.status(500).json({'code': 'FAILED', 'data': err});
		});
};

exports.checkoutController = (req, res) => {
	const { userId, discountCode } = req.body;
	return Promise.resolve(clientServices.checkoutService(userId, discountCode))
		.then(data => {
			return res.status(201).json({'code': 'SUCCESS', data});
		})
		.catch(err => {
			if (err.code === 'EMPTY_CART_ERROR') {
				return res.status(err.statusCode).json({'code': 'FAILED', 'data': err});
			}
			return res.status(500).json({'code': 'FAILED', 'data': err});
		})
};