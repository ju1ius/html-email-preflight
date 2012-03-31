<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="text"
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

<xsl:template match="/">
  <xsl:apply-templates select="*">
    <xsl:with-param name="indent" select="''" />
  </xsl:apply-templates>
</xsl:template>

<xsl:template match="ul/li">

  <xsl:param name="indent" />

  <xsl:text>&#xA;</xsl:text>
  <xsl:value-of select="$indent" />
  <xsl:text>*&#x20;</xsl:text>

  <xsl:apply-templates select="* | text()">
    <xsl:with-param name="indent" select="concat($indent, '  ')" />
  </xsl:apply-templates>

  <xsl:text>&#xA;</xsl:text>

</xsl:template>


<xsl:template match="ol/li">

  <xsl:param name="indent" />

  <xsl:text>&#xA;</xsl:text>
  <!-- indent -->
  <xsl:value-of select="$indent" />
  <!-- numeric bullet -->
  <xsl:for-each select="ancestor-or-self::li[not(parent::ul)]">
    <xsl:value-of select="count(preceding-sibling::li)+1"/>
    <xsl:text>.</xsl:text>
  </xsl:for-each>
  <xsl:text>&#x20;</xsl:text>

  <xsl:apply-templates select="* | text()">
    <xsl:with-param name="indent" select="concat($indent, '  ')" />
  </xsl:apply-templates>

  <xsl:text>&#xA;</xsl:text>

</xsl:template>

<xsl:template match="text()">
  <xsl:param name="indent" />
  <xsl:value-of select="$indent" />
  <xsl:call-template name="indented-text">
    <xsl:with-param name="indent" select="$indent" />
    <xsl:with-param name="text" select="normalize-space(.)" />
  </xsl:call-template>
</xsl:template>

<xsl:template name="indented-text">

  <xsl:param name="indent" />
  <xsl:param name="text" />

  <xsl:value-of select="$indent" />

  <xsl:choose>
    <xsl:when test="contains($text, '&#xA;')">
      <xsl:variable name="line" select="substring-before($text, '&#xA;')" />
      <xsl:variable name="rest" select="substring-after($text, '&#xA;')" />

      <xsl:value-of select="$line" />
      <xsl:text>&#xA;</xsl:text>

      <xsl:if test="$rest">
        <xsl:call-template name="indented-text">
          <xsl:with-param name="indent" select="$indent" />
          <xsl:with-param name="text" select="$rest" />
        </xsl:call-template>
      </xsl:if>
    </xsl:when>
    <xsl:otherwise>
      <xsl:value-of select="$text" />
    </xsl:otherwise>
  </xsl:choose>


</xsl:template>


</xsl:stylesheet>
