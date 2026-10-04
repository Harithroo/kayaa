# Real-device checklist

Print this page (or open it next to the phone) and fill it in. Do it once on **an iPhone in Safari** and once on **an Android phone in Chrome**. A pass means the expected result happened without anything looking broken, cut off, or too small to tap.

| | iPhone (Safari) | Android (Chrome) |
|---|---|---|
| Device and OS version | | |
| Browser version | | |
| Tester and date | | |
| Address tested (staging or the Pages preview) | | |

How to read the tables: tick one box, write a note for anything odd (screenshot it and name the page). Use the **mobile number 071 234 5678** and the order numbers named below; on the preview every sample order uses that number.

Before you start: close other tabs, turn off any ad blocker, and start in a **private tab** so nothing is remembered from an earlier visit.

## 1. Browse and filter

| # | Steps | Expected result | Pass | Fail | Notes |
|---|---|---|:-:|:-:|---|
| 1.1 | Open the home page. Scroll slowly to the bottom. | No sideways scrolling of the page. The hero, the trust strip, "Shop by age" and the product rows all fit the screen. Nothing jumps while the page loads. | ☐ | ☐ | |
| 1.2 | In "Shop by age", swipe the row sideways. | The tiles slide with a small peek of the next tile and a soft fade on the right. No scrollbar shows. "See all sizes" is under the row. | ☐ | ☐ | |
| 1.3 | Tap the **3–6m** tile. | The shop opens already filtered to 3–6m. The size chip 3–6m looks selected. | ☐ | ☐ | |
| 1.4 | On the shop, change **Sort by** to a price order, then switch **Sale only** on and off. | The list reorders and the count changes. The page does not jump. | ☐ | ☐ | |
| 1.5 | Open a category from the menu (for example Bodysuits). | The heading and the intro paragraph sit in the lilac band, then the size chips and the products. | ☐ | ☐ | |
| 1.6 | Use the browser Back button after each of the steps above. | You return to the previous page in the same state. | ☐ | ☐ | |

## 2. Product, bag and quick add

| # | Steps | Expected result | Pass | Fail | Notes |
|---|---|---|:-:|:-:|---|
| 2.1 | Open a product. Swipe the photos. | The photo strip swipes and the dots follow. | ☐ | ☐ | |
| 2.2 | Choose a **colour**, then a **size**. | The chosen colour name and size look selected. A size that is out of stock cannot be chosen. | ☐ | ☐ | |
| 2.3 | Scroll down the page until the main button is out of view. | A sticky "Add to cart" bar appears at the bottom, above nothing else, and does not cover the last lines of the page. | ☐ | ☐ | |
| 2.4 | Tap **Add to cart**. | The bag opens from the side with the new item. The count on the bag icon goes up. | ☐ | ☐ | |
| 2.5 | In the bag drawer, change the quantity, remove an item, then close it with the X, then open it again. | The totals follow. Closing returns you to the page where you were. | ☐ | ☐ | |
| 2.6 | On a product card (home or shop), tap the round **+** quick-add button. | A sheet slides up from the bottom to choose size and colour. Add an item. The sheet closes and the count goes up. | ☐ | ☐ | |
| 2.7 | Open **View bag** (the cart page). Change a quantity. | The page shows each line, the delivery line and the total. The "Checkout" bar stays at the bottom. | ☐ | ☐ | |

## 3. Checkout and payment

| # | Steps | Expected result | Pass | Fail | Notes |
|---|---|---|:-:|:-:|---|
| 3.1 | From the bag tap **Checkout**. Tap into each field in turn. | The right keyboard shows (email, number pad for the mobile). The page does not zoom in when you tap a field. The field is not hidden behind the keyboard. | ☐ | ☐ | |
| 3.2 | Press **Pay now** with the form empty. | An error summary appears at the top, and each field shows its own message. Tapping a message moves to the field. | ☐ | ☐ | |
| 3.3 | Fill the form (any email, 071 234 5678, a name, address line, city, district Colombo). | Choosing the district shows the delivery estimate. | ☐ | ☐ | |
| 3.4 | Press **Pay now** (card). | The button shows "Redirecting to secure payment...", then the stand-in payment page opens. Its four buttons lead to the four thank-you states. | ☐ | ☐ | |
| 3.5 | Open the checkout again with the second payment option on: add `?demo=cod-on` to the address. | A "Payment method" box shows two cards, Card and the cash option. Card is selected. | ☐ | ☐ | |
| 3.6 | Tap the cash card. | The card is highlighted and has a filled ring. The button changes to "Place order" and the text under it changes. Tap the card option again: it returns to "Pay now". | ☐ | ☐ | |
| 3.7 | With the cash card selected, fill the form and press **Place order**. | The thank-you page says "Order confirmed", "Thank you, {name}", "You'll pay {total} to the courier on delivery." and shows the "Pay on delivery" badge. The bag is empty. | ☐ | ☐ | |
| 3.8 | On the thank-you page tap **Copy** next to the order number, then paste it somewhere. | "Copied" shows and the pasted text is the order number. | ☐ | ☐ | |

## 4. Track an order

| # | Steps | Expected result | Pass | Fail | Notes |
|---|---|---|:-:|:-:|---|
| 4.1 | Open **Track order**. Enter `KY-261001-K8D3` and the **right** mobile number 071 234 5678. | The order shows its status steps, items and totals. No name, address or phone is shown. | ☐ | ☐ | |
| 4.2 | Enter the same order number with a **wrong** mobile number (077 999 9999). | A "couldn't find that order" message appears. It does not say which part was wrong. | ☐ | ☐ | |
| 4.3 | Track `KY-261001-C0D1` with the right number. | The page shows "Cash on delivery" as the method and the "Pay on delivery" badge. There is no "Resume payment" button. | ☐ | ☐ | |
| 4.4 | Track `KY-260930-C0D2`. | The badge reads "Paid on delivery". | ☐ | ☐ | |

## 5. Account, forms and saving

| # | Steps | Expected result | Pass | Fail | Notes |
|---|---|---|:-:|:-:|---|
| 5.1 | Open **Sign in** and press the button with empty fields. | An error summary appears. The password field has a show/hide button that works. | ☐ | ☐ | |
| 5.2 | Open **Create an account** and fill it with test details. | The next page asks to verify the email. | ☐ | ☐ | |
| 5.3 | Open **My orders** (account, using the demo sign-in). Open an order, then tap **Cancel order** on `KY-261003-A3F9`. | A dialog asks to confirm. **Keep order** closes it. Cancelling shows the confirmation. | ☐ | ☐ | |
| 5.4 | Open **Contact**. Send the form empty, then filled. | Errors first, then a "message sent" confirmation. | ☐ | ☐ | |
| 5.5 | Open a product and tap **Save** (the heart). Open the Wishlist. | The product is listed and the heart looks filled. | ☐ | ☐ | |

## 6. Menus

| # | Steps | Expected result | Pass | Fail | Notes |
|---|---|---|:-:|:-:|---|
| 6.1 | Tap the menu (three lines). | The drawer slides in from the left. The page behind does not scroll. Tap outside it, or the X: it closes. | ☐ | ☐ | |
| 6.2 | In the drawer open **Shop by size** and tap a size. | The shop opens filtered to that size. | ☐ | ☐ | |
| 6.3 | Use the bottom tab bar: Home, Shop, Search, Bag, Account. | Each opens the right place. The current tab is marked. | ☐ | ☐ | |
| 6.4 | Tablet only: turn a tablet or a large phone sideways and use the header menu with its **Shop** chevron. | The big shop menu opens, closes with Esc or a tap outside. (Not used on a phone.) | ☐ | ☐ | |

## 7. Phone settings

| # | Steps | Expected result | Pass | Fail | Notes |
|---|---|---|:-:|:-:|---|
| 7.1 | **Landscape.** Turn the phone sideways on the home page, a product, the cart and the checkout. | Nothing is cut off. The bottom bars do not cover more than a third of the screen. | ☐ | ☐ | |
| 7.2 | **Large text.** iPhone: Settings > Display and Brightness > Text Size, drag to the right. Android: Settings > Display > Font size, largest. Reload the home page, product and checkout. | Text is bigger. Nothing overlaps or is cut off. Buttons still work. | ☐ | ☐ | |
| 7.3 | **Slow network.** Chrome on a computer connected to the phone, or the Chrome dev tools: Network > Slow 4G. Load the home page. | Text appears quickly in a plain font, then swaps without the layout moving. The page is usable within a few seconds. | ☐ | ☐ | |
| 7.4 | **Dark mode.** Turn the phone's dark mode on. Reload the home page, the product and the checkout. | The site **stays light**. The address bar and status bar colour is a pale lilac. Nothing turns dark or unreadable. | ☐ | ☐ | |
| 7.5 | **Add to home screen.** iPhone: Share > Add to Home Screen. Android: menu > Add to Home screen. | It can be added. (It has no custom icon yet: this is expected until a logo exists.) | ☐ | ☐ | |
| 7.6 | **Reduce motion.** Turn on Reduce Motion (iPhone: Accessibility > Motion; Android: Remove animations). Open the bag drawer and the quick-add sheet. | They appear without sliding or fading. | ☐ | ☐ | |

## Result

| | iPhone (Safari) | Android (Chrome) |
|---|---|---|
| Number of failed lines | | |
| Anything blocking a client review? | ☐ no  ☐ yes | ☐ no  ☐ yes |
| Signed off by | | |

Send failures to the developer with: the line number, the device and version, a screenshot, and what you expected.
