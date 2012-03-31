<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
    omit-xml-declaration="yes"
    encoding="utf-8"
    indent="no" />

<xsl:strip-space elements="*" />

<!-- Ninja HTML Technique (http://chaoticpattern.com/article/manipulating-html-in-xml/) -->

<xsl:template match="*" mode="html">
	<xsl:element name="{name()}">
		<xsl:apply-templates select="* | @* | text()" mode="html"/>
	</xsl:element>
</xsl:template>

<xsl:template match="@*" mode="html">
	<xsl:attribute name="{name(.)}">
		<xsl:value-of select="."/>
	</xsl:attribute>
</xsl:template>

<!-- here we go -->

<xsl:template match="*">
	<xsl:apply-templates select="*"/>
</xsl:template>

<xsl:template match="ul/li">
  <text indent="  ">
    <xsl:attribute name="par-prefix">
      <xsl:text>*&#x20;</xsl:text>
    </xsl:attribute>
    <xsl:apply-templates select="* | text()" />
  </text>
</xsl:template>

<xsl:template match="ol/li">
  <text indent="  ">
    <xsl:attribute name="par-prefix">
      <!-- numeric bullet -->
      <xsl:for-each select="ancestor-or-self::li[not(parent::ul)]">
        <xsl:value-of select="count(preceding-sibling::li)+1"/>
        <xsl:text>.</xsl:text>
      </xsl:for-each>
      <xsl:text>&#x20;</xsl:text>
    </xsl:attribute>
    <xsl:apply-templates select="* | text()" />
  </text>
</xsl:template>

<xsl:template match="blockquote">
  <text indent="&gt; ">
    <xsl:apply-templates select="* | text()" />
  </text>
</xsl:template>


</xsl:stylesheet>
