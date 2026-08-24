Option Explicit

Dim app, doc, importFilter, shapes
Dim pngPath, cdrPath

pngPath = "C:\laragon\www\24logistru\output\advertising\logistru-a4.png"
cdrPath = "C:\laragon\www\24logistru\output\advertising\logistru-a4.cdr"

Set app = CreateObject("CorelDRAW.Application.27")
app.Visible = False

Set doc = app.CreateDocument()
doc.Unit = 3
doc.ActivePage.SetSize 210, 297

Set importFilter = doc.ActiveLayer.ImportEx(pngPath, 0)
importFilter.Finish

Set shapes = doc.ActiveSelectionRange
shapes.SetSize 210, 297
shapes.SetPosition 105, 148.5

doc.SaveAs cdrPath
doc.Close
app.Quit

WScript.Echo cdrPath
