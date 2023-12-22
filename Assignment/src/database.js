exports.Inventory = [
	{
		id: "1",
		name: 'item1',
		price: '300 INR',
		count: 30
	},
	{
		id: "2",
		name: 'item2',
		price: '400 INR',
		count: 321
	},
	{
		id: "3",
		name: 'item3',
		price: '4300 INR',
		count: 310
	},
	{
		id: "4",
		name: 'item4',
		price: '3300 INR',
		count: 20
	},
	{
		id: "5",
		name: 'item5',
		price: '5300 INR',
		count: 3120
	},
	{
		id: "6",
		name: 'item6',
		price: '3006 INR',
		count: 123
	},
	{
		id: "7",
		name: 'item7',
		price: '30032 INR',
		count: 310
	},
	{
		id: "8",
		name: 'item8',
		price: '800 INR',
		count: 130
	}
];

/**
 * Cart = [{
 * 	itemId: String,
 * 	itemName: String,
 * 	price: String,
 * 	userId: String,
 * 	count: Number
 * }]
 */
exports.Cart = [];

exports.PurchasedItems = [
	{
		id: 12,
		name: 'item 12',
		price: '234 INR',
		discountCode: null,
		userId: '1'
	},
	{
		id: 14,
		name: 'item 14',
		price: '214 INR',
		discountCode: 'CODE12',
		userId: '5'
	}
];

exports.Orders = [
	{
		userId: 'userId1',
		orderTotalPrice: '9999 INR',
		appliedDiscountCode: null,
		discountedOrderPrice: null,
		orderedItems: [
			{
				itemId: '133',
				itemName: 'item 133',
				price: '6666 INR',
				count: 10,
				userId: 'userId'
			},
			{
				itemId: '145',
				itemName: 'item 145',
				price: '3333 INR',
				count: 21,
				userId: 'userId'
			}
		]
	},
	{
		userId: 'userId2',
		orderTotalPrice: '9999 INR',
		appliedDiscountCode: 'CODE!@#',
		discountedOrderPrice: '7689 INR',
		orderedItems: [
			{
				itemId: '133',
				itemName: 'item 133',
				price: '6666 INR',
				count: 10,
				userId: 'userId2'
			},
			{
				itemId: '148',
				itemName: 'item 148',
				price: '3333 INR',
				count: 21,
				userId: 'userId2'
			}
		]
	}
];

// Each coupon code is worth discount of 10%
exports.DiscountCodes = [];