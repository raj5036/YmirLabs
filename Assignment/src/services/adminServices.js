const { DiscountCodes, Orders } = require("../database");
const { ERROR } = require("../handlers/error");

exports.generateDiscountCode = (discountCode) => {
	// Check if discountCode is already present
	if (DiscountCodes.includes(discountCode)) {
		throw ERROR.DUPLICATE_DISCOUNT_CODE;
	}

	DiscountCodes.push(discountCode);
	return DiscountCodes;
};

exports.generateStoreSummary = () => {
	const purchaseCount = {}; 

	// Store purchase count as a dictionary {"item-ID": orderedCount}
	Orders.forEach(order => {
		order.orderedItems.forEach(item => {
			const { itemId, count } = item;
			const key = 'Item-ID-' + itemId;
			if (purchaseCount[key]) {
				purchaseCount[key] += count
			} else {
				purchaseCount[key] = count;
			}
		});
	});

	// Sum up total discounted amount for all Orders
	const totalDiscountedAmount = Orders.filter(order => order.appliedDiscountCode).reduce((total, order) => {
		const discountedAmount = parseFloat(order.orderTotalPrice) - parseFloat(order.discountedOrderPrice);
		return total + discountedAmount;
	}, 0);
	
	let storeSummary = {
		purchaseCount,
		purchasedItems: Object.keys(purchaseCount).map(key => key),
		discountCodes: DiscountCodes,
		totalDiscountedAmount,
	};
	return {storeSummary};
};