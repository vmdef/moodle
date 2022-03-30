This block displays a random local partner ad for the given country
based on detecting the user's IP geo-location. The block can be
installed on any community site. Additionally to displaying the
partner ads, it can also be used for promoting official moots and other
events.

- For legacy reasons, the primary list of partners is located in the
  table `register_ads` on moodle.org.
- Other sites fetch the list from moodle.org via a web service and store
  them in the block's own table `block_partners_ads`.
- To make it even more confusing, the primary location of the images
  used in ads is the partners.moodle.com site but the images are then
  synced to moodle.org and served from there.

It is a mess.
