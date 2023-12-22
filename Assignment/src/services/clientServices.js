const { Inventory, Cart, Orders, DiscountCodes } = require("../database");
const { ERROR } = require("../handlers/error");
const { DISCOUNT_FREQUENCY } = require("../utils/constants");


exports.addToCartService = (itemId, userId, desiredCount) => {
	// Check if such Item is present in Inventory
	const availableItem = Inventory.filter(item => item.id === itemId);
	if (!availableItem.length) {
		throw ERROR.ITEM_NOT_FOUND;
	}

	// Check if User already has this Item in Cart
	const isAlreadyInCart = Cart.some(item => item.itemId === itemId && userId === userId);
	if (isAlreadyInCart) {
		throw ERROR.ITEM_ALREADY_IN_CART;
	}

	// Check if enough number of this Item is there in Inventory
	if (desiredCount > availableItem[0].count) {
		throw ERROR.ITEM_COUNT_SHORTAGE;	
	}

	// If Item is available, push it to Cart.
	Cart.push({
		itemId: availableItem[0].id,
		itemName: availableItem[0].name,
		price: availableItem[0].price,
		count: desiredCount,
		userId,
	});
	return Cart;
};

exports.checkoutService = (userId, discountCode) => {
	// Check if Cart is empty for current user
	const userCartItems = Cart.filter(item => item.userId === userId);
	if (!userCartItems.length) {
		throw ERROR.EMPTY_CART_ERROR;
	}

	// Transfer Cart Items to 'Orders' store
	const orderSummary = {
		userId,
		orderTotalPrice: `${userCartItems.reduce((total, item) => {
			return total + parseFloat(item.price)
		}, 0)} INR`,
		...discountCalculator(userCartItems, discountCode),
		orderedItems: userCartItems
	};
	Orders.push(orderSummary);

	// Clean up Inventory
	Cart.filter(item => item.userId === userId).forEach(cartItem => {
		const index = Inventory.findIndex(inventoryItem => inventoryItem.id === cartItem.itemId);
		Inventory[index].count -= cartItem.count;
	})
	
	// Remove Items from Cart
	Cart = Cart.filter(item => item.userId !== userId);

	return orderSummary;
};

const discountCalculator = (userCartItems, discountCode) => {
	// Check if discount needs to be applied
	const currentOrderNumber = Orders.length + 1;
	let discountSummary = {
		appliedDiscountCode: null,
		discountedOrderPrice: null
	};
	if (currentOrderNumber % DISCOUNT_FREQUENCY == 0) {
		const appliedDiscountCode = isDiscountCodeValid(discountCode) ? discountCode : DiscountCodes.shift();
		const discountedOrderPrice = (userCartItems.reduce((total, userCartItem) => {
			return total + parseFloat(userCartItem.price);
		}, 0)) * 0.1;
		discountSummary['appliedDiscountCode'] = appliedDiscountCode;
		discountSummary['discountedOrderPrice'] = discountedOrderPrice;
	}

	return discountSummary;
};

const isDiscountCodeValid = (userSubmittedDiscountCode) => {
	return DiscountCodes.some(discountCode => discountCode === userSubmittedDiscountCode);
};