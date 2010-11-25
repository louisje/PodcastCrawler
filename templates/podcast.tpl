<?xml version="1.0" encoding="utf-8"?>
<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0">
<channel>
<title>{{ channel.title }}</title>
<link>{{ channel.link }}</link>
<language>{{ channel.language }}</language>
<copyright>{{ channel.copyright }}</copyright>
<itunes:subtitle>{% if channel.subtitle %}{{ channel.subtitle }}{% else %}{{ channel.title }}{% endif %}</itunes:subtitle>
<itunes:author>{{ channel.author }}</itunes:author>
<itunes:summary>{% if channel.summary %}{{ channel.summary }}{% else %}{{ channel.description }}{% endif %}</itunes:summary>
<description>{{ channel.description }}</description>
<itunes:owner>
	<itunes:name>{{ channel.owner.name }}</itunes:name>
	<itunes:email>{{ channel.owner.email }}</itunes:email>
</itunes:owner>
<itunes:image href="{{ channel.image }}" />
<!-- Not impletement in prototype
<itunes:category text="Technology">
<itunes:category text="Gadgets"/>
</itunes:category>
<itunes:category text="TV &amp; Film"/>
-->
{% for episode in episodes %}
<item>
<title>{{ episode.title }}</title>
<itunes:author>{{ episode.author }}</itunes:author>
<itunes:subtitle>{% if episode.subtitle %}{{ episode.subtitle }}{% else %}{{ episode.title }}{% endif %}</itunes:subtitle>
<itunes:summary>{{ episode.summary }}</itunes:summary>
<enclosure url="{{ episode.media.url }}" length="{% if episode.media.length %}{{ episode.media.length }}{% else %}10000000{% endif %}" type="{{episode.media.type}}" />
<guid>{{ episode.guid }}</guid>
<pubDate>{{ episode.pubDate }}</pubDate>
<description>{{ episode.description }}</description>
<itunes:duration>{% if episode.duration %}{{ episode.duration }}{% else %}6:00{% endif %}</itunes:duration>
<itunes:keywords>{{ episode.keywords }}</itunes:keywords>
<itunes:image href="{{ episode.image }}" />
</item>
{% endfor %}
<!-- "item" example 
<item>
<title>Socket Wrench Shootout</title>
<itunes:author>Jane Doe</itunes:author>
<itunes:subtitle>Comparing socket wrenches is fun!</itunes:subtitle>
<itunes:summary>This week we talk about metric vs. old english socket wrenches. Which one is better? Do you really need both? Get all of your answers here.</itunes:summary>
<enclosure url="http://example.com/podcasts/everything/AllAboutEverythingEpisode2.mp3" length="5650889" type="audio/mpeg" />
<guid>http://example.com/podcasts/archive/aae20050608.mp3</guid>
<pubDate>Wed, 8 Jun 2005 19:00:00 GMT</pubDate>
<itunes:duration>4:34</itunes:duration>
<itunes:keywords>metric, socket, wrenches, tool</itunes:keywords>
</item>
-->
</channel>
</rss>
