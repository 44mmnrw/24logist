Option Explicit

Dim app, doc, importFilter, importOptions, saveOptions, rootShape
Dim svgPath, cdrPath, textBefore, textAfter

svgPath = "C:\laragon\www\24logistru\output\advertising\logistru-videoconference-16x9-v2.svg"
cdrPath = "C:\laragon\www\24logistru\output\advertising\logistru-videoconference-16x9-v2-curves-clean-temp.cdr"

Set app = CreateObject("CorelDRAW.Application.27")
app.Visible = False
Set doc = app.CreateDocument()
doc.Unit = 3
doc.ActivePage.SetSize 320, 180

Set importOptions = app.CreateStructImportOptions()
Set importFilter = doc.ActiveLayer.ImportEx(svgPath, 0, importOptions)
importFilter.Finish

Set rootShape = doc.ActivePage.Shapes.Item(1)
rootShape.SetSize 320, 180
rootShape.CenterX = 160
rootShape.CenterY = 90

textBefore = CountText(doc.ActivePage.Shapes)
ConvertTextToCurves doc.ActivePage.Shapes
textAfter = CountText(doc.ActivePage.Shapes)

Set saveOptions = app.CreateStructSaveAsOptions()
doc.SaveAs cdrPath, saveOptions
doc.Close
WScript.Echo "CDR=" & cdrPath
WScript.Echo "TEXT_BEFORE=" & textBefore
WScript.Echo "TEXT_AFTER=" & textAfter
On Error Resume Next
app.Quit
On Error GoTo 0

Sub ConvertTextToCurves(shapeCollection)
  Dim index, currentShape
  For index = shapeCollection.Count To 1 Step -1
    Set currentShape = shapeCollection.Item(index)
    If currentShape.Type = 6 Then
      currentShape.ConvertToCurves
    ElseIf currentShape.Type = 7 Then
      ConvertTextToCurves currentShape.Shapes
    End If
  Next
End Sub

Function CountText(shapeCollection)
  Dim index, currentShape, total
  total = 0
  For index = 1 To shapeCollection.Count
    Set currentShape = shapeCollection.Item(index)
    If currentShape.Type = 6 Then total = total + 1
    If currentShape.Type = 7 Then total = total + CountText(currentShape.Shapes)
  Next
  CountText = total
End Function
