<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:str="http://exslt.org/strings"
    extension-element-prefixes="str">

    <xsl:output method="xml" indent="yes"/>
    <xsl:param name="nodes_str"/>
    <xsl:param name="containers_str"/>

    <xsl:template match="/">
	<tree>
	    <nodes>
		<xsl:apply-templates select="//nodes/node"/>
	    </nodes>
	    <criterions>
		<xsl:apply-templates select="//criterion"/>
	    </criterions>
	    <xsl:apply-templates select="//dependences-between-classes"/>
	    <dependences-between-criterions>
		<xsl:apply-templates select="//dependences-between-criterions/dependence"/>
	    </dependences-between-criterions>
	</tree>
    </xsl:template>

    <xsl:template match="node">
	<xsl:variable name="node_id" select="./@id"/>
	<xsl:variable name="current_node" select="."/>
	<xsl:if test="contains($containers_str, concat('^', $node_id, '$'))">
	    <xsl:copy-of select="."/>
	</xsl:if>
    </xsl:template>

    <xsl:template match="criterion">
	<xsl:variable name="criterion_id" select="./@id"/>
	<xsl:if test="contains($nodes_str,concat('^', $criterion_id, '_'))">
	    <xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:for-each select="condition">
		    <xsl:variable name="condition_value" select="./@value"/>
		    <xsl:if test="contains($nodes_str,concat($criterion_id, '_', $condition_value))">
			<xsl:copy-of select="."/>
		    </xsl:if>
		</xsl:for-each>
	    </xsl:copy>
	</xsl:if>
    </xsl:template>

    <xsl:template match="//dependences-between-criterions/dependence">
	<xsl:variable name="tmp" select="."/>
	<xsl:if test="./@type='each'">
	    <xsl:copy-of select="$tmp"/>
	</xsl:if>
	<xsl:if test="./@type='at-least-one'">
	    <xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:for-each select="./dependent-criterion">
		    <xsl:variable name="dependent-criterion-id" select="./@id"/>
		    <xsl:if test="contains($nodes_str, concat('^', $dependent-criterion-id, '_'))">
			<xsl:copy-of select="."/>
		    </xsl:if>
		</xsl:for-each>
	    </xsl:copy>
	</xsl:if>
    </xsl:template>

    <xsl:template match="//dependences-between-classes">
	<xsl:copy-of select="."/>
    </xsl:template>
</xsl:stylesheet>
