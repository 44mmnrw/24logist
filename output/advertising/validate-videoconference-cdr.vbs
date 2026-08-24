Option Explicit

Dim app, doc, cdrPath, rootShape
cdrPath = "C:\laragon\www\24logistru\output\advertising\logistru-videoconference-16x9-curves.cdr"

Set app = CreateObject("CorelDRAW.Application.27")
app.Visible = False
Set doc = app.OpenDocument(cdrPath, 0)
doc.Unit = 3
Set rootShape = doc.ActivePage.Shapes.Item(1)

WScript.Echo "PAGE_MM=" & Round(doc.ActivePage.SizeWidth, 2) & "x" & Round(doc.ActivePage.SizeHeight, 2)
WScript.Echo "ROOT_MM=" & Round(rootShape.SizeWidth, 2) & "x" & Round(rootShape.SizeHeight, 2)
WScript.Echo "ROOT_CENTER=" & Round(rootShape.CenterX, 2) & "," & Round(rootShape.CenterY, 2)
WScript.Echo "TEXT_OBJECTS=" & CountType(doc.ActivePage.Shapes, 6)
WScript.Echo "CURVE_OBJECTS=" & CountType(doc.ActivePage.Shapes, 3)
WScript.Echo "BITMAP_OBJECTS=" & CountType(doc.ActivePage.Shapes, 5)

doc.Close
On Error Resume Next
app.Quit
On Error GoTo 0

Function CountType(shapeCollection, requestedType)
  Dim index, currentShape, total
  total = 0
  For index = 1 To shapeCollection.Count
    Set currentShape = shapeCollection.Item(index)
    If currentShape.Type = requestedType Then total = total + 1
    If currentShape.Type = 7 Then total = total + CountType(currentShape.Shapes, requestedType)
  Next
  CountType = total
End Function
