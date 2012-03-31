<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:txt="http://github.com/ju1ius/premailer">
  
<xsl:output method="xml"
    omit-xml-declaration="no"
    encoding="utf-8"
    indent="yes" />

<!--<xsl:strip-space elements="*" />-->

<!-- Ninja HTML Technique (http://chaoticpattern.com/article/manipulating-html-in-xml/) -->

<xsl:template match="*" mode="html">
	<xsl:element name="{name()}">
		<xsl:apply-templates select="* | @* | text()"/>
	</xsl:element>
</xsl:template>

<xsl:template match="@*" mode="html">
	<xsl:attribute name="{name(.)}">
		<xsl:value-of select="."/>
	</xsl:attribute>
</xsl:template>

<!-- here we go -->

<xsl:template match="/">
  <txt:document>
	  <xsl:apply-templates select="*" />
  </txt:document>
</xsl:template>

<!-- Unhandled tags are replaced by their node value -->

<xsl:template match="*[not(
  html     | head       | body | 
  a        | img        | hr   | br     | em     | i        | strong | b | 
  li       | blockquote | p    | pre    | code   | ul  >    | ol     | 
  h1       | h2         | h3   | h4     | h5     | h6       | 
  table    | tr         | td   |
  fieldset | form       | map  | object | script | noscript | iframe
)]" priority="-1">
  <xsl:value-of select="." />
</xsl:template>


<!-- line-breaks -->

<xsl:template match="br">
	<xsl:text>&#x20;&#x20;</xsl:text>
	<xsl:text>&#xA;</xsl:text>
</xsl:template>

<!-- links -->

<xsl:template match="a">
  <xsl:text>[</xsl:text>
  <xsl:apply-templates select="* | text()"/>
  <xsl:text>](</xsl:text>
  <xsl:value-of select="@href"/>
  <xsl:if test="@title != ''">
    <xsl:text>&#x20;"</xsl:text>
    <xsl:value-of select="@title"/>
    <xsl:text>"</xsl:text>
  </xsl:if>
  <xsl:text>)</xsl:text>
	<xsl:if test="parent::div">
		<xsl:text>&#xA;&#xA;</xsl:text>
	</xsl:if>
</xsl:template>

<xsl:template match="img[@alt and not(contains(@class, 'separator'))]">
  <xsl:value-of select="@alt" />
</xsl:template>

<xsl:template match="hr|img[contains(@class, 'separator')]">
  <txt:separator char="-" />
</xsl:template>


<!-- em, strong -->

<xsl:template match="em|i">
  <xsl:text>_</xsl:text>
  <xsl:apply-templates select="* | text()"/>
  <xsl:text>_</xsl:text>
</xsl:template>

<xsl:template match="strong|b" >
  <xsl:text>__</xsl:text>
  <xsl:apply-templates select="* | text()"/>
  <xsl:text>__</xsl:text>
</xsl:template>

<!-- p, br, hr -->

<xsl:template match="p">
  <txt:block lines-after="1">
	  <xsl:apply-templates select="* | text()"/>
  </txt:block>
</xsl:template>

<!-- pre -->

<xsl:template match="pre">
  <txt:block raw="true" lines-after="1">
    <!--<xsl:value-of select="text()" />-->
    <xsl:apply-templates select="* | text()" />
  </txt:block>
</xsl:template>

<!-- h1, h2 -->

<xsl:template match="h1" >
  <txt:block lines-after="1" lines-before="2" border-bottom="=" border-top="=">
    <xsl:apply-templates select="* | text()" />
  </txt:block>
</xsl:template>

<xsl:template match="h2" >
  <txt:block lines-after="1" lines-before="2" border-bottom="-">
    <xsl:apply-templates select="* | text()" />
  </txt:block>
</xsl:template>

<!-- h3, h4, h5, h6 -->

<xsl:template match="h3" >
  <txt:block lines-after="1" lines-before="2" box="+,-,+,|,+,-,+,|">
    <xsl:apply-templates select="* | text()" />
  </txt:block>
</xsl:template>

<xsl:template match="h4" >
  <txt:block lines-after="1" lines-before="2" bullet="###### ">
    <xsl:apply-templates select="* | text()" />
  </txt:block>
</xsl:template>

<xsl:template match="h5" >
  <txt:block lines-after="1" lines-before="2" bullet="######### ">
    <xsl:apply-templates select="* | text()" />
  </txt:block>
</xsl:template>

<xsl:template match="h6" >
  <txt:block lines-after="1" lines-before="2" bullet="############ ">
    <xsl:apply-templates select="* | text()" />
  </txt:block>
</xsl:template>

<!-- lists -->

<xsl:template match="ul/li">
  <txt:block indent="  " bullet="* ">
    <xsl:apply-templates select="* | text()" />
  </txt:block>
</xsl:template>

<xsl:template match="ol/li">
  <txt:block indent="  ">
    <xsl:attribute name="bullet">
      <!-- numeric bullet -->
      <xsl:for-each select="ancestor-or-self::li[not(parent::ul)]">
        <xsl:value-of select="count(preceding-sibling::li)+1"/>
        <xsl:text>.</xsl:text>
      </xsl:for-each>
      <xsl:text>&#x20;</xsl:text>
    </xsl:attribute>
    <xsl:apply-templates select="* | text()" />
  </txt:block>
</xsl:template>

<!-- blockquotes -->

<xsl:template match="blockquote">
  <txt:block indent="&gt; ">
    <xsl:apply-templates select="* | text()" />
  </txt:block>
</xsl:template>

<!-- Tables -->

<xsl:template match="table">
  <txt:block lines-after="1">
    <xsl:apply-templates select="*" />
  </txt:block>
</xsl:template>

<xsl:template match="tr">
  <txt:block lines-after="1">
	  <xsl:apply-templates select="*" />
  </txt:block>
</xsl:template>

<xsl:template match="td|th">
  <txt:block lines-after="1">
	  <xsl:apply-templates select="* | text()" />
  </txt:block>
</xsl:template>


<!-- ASCII Tables -->

<xsl:template match="table[@data-toplaintext]">
  <txt:table>
    <xsl:apply-templates select="*" mode="table"/>
  </txt:table>
</xsl:template>

<xsl:template match="tr" mode="table">
  <txt:tr>
    <xsl:apply-templates select="*" mode="table" />
  </txt:tr>
</xsl:template>

<xsl:template match="td|th" mode="table">
  <txt:td>
    <xsl:apply-templates select="* | text()" />
  </txt:td>
</xsl:template>

</xsl:stylesheet>
