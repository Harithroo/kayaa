# Fabrics (Home page, "Feel it before you buy it")

This copy is PROPOSED. It comes from the client-approved Home concept (design/home-v2-concept.html), but the fabric claims (feel, best for, care) are not confirmed facts about the real garments. [[PROPOSED: the four fabric descriptions, "Feels", "Best for" and "Care" lines below. Confirm with the client against the real products' care labels before launch.]]

Each `##` block is one fabric swatch tab on the Home page. tools/catalogue.mjs reads this file (`gen: home-fabric-tabs`, `gen: home-fabric-panels`); edit the file, not the HTML.

- `key` picks the drawn texture (muslin, jersey, rib, waffle: assets/js/home/garments.js).
- `colour` is the cloth colour. `stiff` and `flutter` tune the cloth model (1 is the middle; a higher `stiff` hangs flatter, a higher `flutter` moves more in the breeze).
- `match` lists the words that find the real products "Found in": `tag:<slug>` matches a product tag, `name:<word>` matches the product name. When nothing matches, the page links to a search for the fabric name instead.

## Muslin
- key: muslin
- colour: #FFFFFF
- line: Open, airy cotton gauze that gets softer with every wash.
- feel: Light and gently crinkled
- best: Swaddles, sleep bags, burp cloths
- care: Machine wash cool, line dry
- stiff: 0.8
- flutter: 1.25
- match: tag:muslin, name:muslin

## Bamboo jersey
- key: jersey
- colour: #DCCDED
- line: Smooth and cool against the skin, with a soft stretch.
- feel: Silky, cool, stretchy
- best: Sleepsuits for warm nights
- care: Gentle wash, no tumble dry
- stiff: 0.9
- flutter: 1
- match: tag:bamboo, name:bamboo

## Ribbed cotton
- key: rib
- colour: #C6D6EC
- line: Stretchy ribs that move with every kick and keep their shape.
- feel: Springy and snug
- best: Everyday bodysuits
- care: Machine wash warm
- stiff: 1
- flutter: 0.85
- match: name:ribbed

## Waffle knit
- key: waffle
- colour: #C9B6DE
- line: Little raised squares that hold a touch of warmth for rainy evenings.
- feel: Textured and cosy
- best: Cardigans and layers
- care: Wash inside out, dry flat
- stiff: 1.15
- flutter: 0.65
- match: name:waffle
