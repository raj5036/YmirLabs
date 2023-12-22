exports.ERROR = {
	ITEM_NOT_FOUND: {
		statusCode: 404,
        code: 'ITEM_NOT_FOUND',
        message: 'Item is not present in Inventory.',
	},
	ITEM_COUNT_SHORTAGE: {
		statusCode: 400,
		code: 'ITEM_COUNT_SHORTAGE',
		message: 'There is a shortage for this Item. Please reduce the desired count for this item.'
	},
	ITEM_ALREADY_IN_CART: {
		statusCode: 409,
		code: 'ITEM_ALREADY_IN_CART',
		message: 'This item is already present in your Cart.'
	},
	EMPTY_CART_ERROR: {
		statusCode: 400,
		code: 'EMPTY_CART_ERROR',
		message: 'Cart is empty.'
	},
	DUPLICATE_DISCOUNT_CODE: {
		statusCode: 409,
		code: 'DUPLICATE_DISCOUNT_CODE',
		message: 'This Discount code is already present in the system.'
	},
};