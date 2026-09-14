export function getItemQuantity(item) {
  const quantity = Number(item && item.quantity)
  return Number.isSafeInteger(quantity) && quantity > 0 ? quantity : 1
}

export function getTotalItemCount(items) {
  if (!Array.isArray(items)) {
    return 0
  }
  return items.reduce((total, item) => total + getItemQuantity(item), 0)
}

export function getSingleItemPrice(item, fallback) {
  return item && item.price != null ? item.price : fallback
}
