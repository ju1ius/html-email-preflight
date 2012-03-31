<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:import href="html2text.xsl"/>

<xsl:output method="xml"
    omit-xml-declaration="yes"
    encoding="utf-8"
    indent="yes" />

  <xsl:template match="/html/body">
    <xsl:apply-templates select="." mode="email"/>
  </xsl:template>

</xsl:stylesheet>
