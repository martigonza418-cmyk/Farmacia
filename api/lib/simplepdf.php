<?php
class SimplePDF {
    protected $lines = [];
    protected $title = '';
    public function __construct($orientation='P',$unit='mm',$size='A4') {}
    public function AddPage() {}
    public function SetFont($family,$style='',$size=12) { $this->fontSize = $size; }
    public function Cell($w,$h,$txt='',$border=0,$ln=0,$align='') {
        $this->lines[] = $txt;
    }
    public function Ln($h=6) { $this->lines[] = "\n"; }
    public function Output($dest='I',$name='document.pdf') {
        $content = "%PDF-1.4\n%âãÏÓ\n";
        $body = "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n";
        $body .= "2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n";
        $text = implode("\n", array_map('trim',$this->lines));
        $stream = "BT /F1 12 Tf 50 750 Td (" . $this->pdfEscape($text) . ") Tj ET";
        $stream_len = strlen($stream);
        $body .= "3 0 obj<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /MediaBox [0 0 595 842] /Contents 5 0 R >>endobj\n";
        $body .= "4 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\n";
        $body .= "5 0 obj<< /Length $stream_len >>stream\n$stream\nendstream\nendobj\n";
        $xref_pos = strlen($content . $body);
        $content .= $body;
        $xref = "xref\n0 6\n0000000000 65535 f \n";
        $content .= $xref;
        $content .= "trailer<< /Root 1 0 R >>\nstartxref\n" . strlen($content) . "\n%%EOF";
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        echo $content;
    }
    protected function pdfEscape($s) {
        return str_replace(['\\','(',')'], ['\\\\','\\(','\\)'], $s);
    }
}
?>
