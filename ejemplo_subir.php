<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subida de Archivos PDF</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .upload-area {
            border: 2px dashed #007bff;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover {
            background-color: #e9ecef;
            border-color: #0056b3;
        }
        .file-list {
            max-height: 200px;
            overflow-y: auto;
        }
        .folder-badge {
            font-size: 0.8em;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Subir Archivos PDF</h4>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" action="upload.php" method="POST" enctype="multipart/form-data">
                            <!-- Área de arrastrar y soltar -->
                            <div class="upload-area mb-4" id="dropArea">
                                <div class="mb-3">
                                    <i class="bi bi-cloud-upload fs-1 text-primary"></i>
                                    <h5>Arrastra y suelta tus archivos PDF aquí</h5>
                                    <p class="text-muted">o haz clic para seleccionar</p>
                                </div>
                                <input type="file" id="fileInput" name="archivos[]" multiple accept=".pdf" class="d-none">
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('fileInput').click()">
                                    Seleccionar Archivos
                                </button>
                            </div>

                            <!-- Lista de archivos seleccionados -->
                            <div id="fileList" class="mb-3" style="display: none;">
                                <h6>Archivos seleccionados:</h6>
                                <div class="file-list p-2 border rounded" id="selectedFiles">
                                    <!-- Los archivos aparecerán aquí -->
                                </div>
                            </div>

                            <!-- Información sobre distribución -->
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle"></i> Información importante:</h6>
                                <ul class="mb-2">
                                    <li>Archivos que contienen <strong>"gl"</strong> → Carpeta <span class="badge bg-success">GLAMA</span></li>
                                    <li>Archivos que contienen <strong>"mor"</strong> → Carpeta <span class="badge bg-warning text-dark">MORYSAN</span></li>
                                </ul>
                                <p class="mb-0"><strong>⚠️ Nota:</strong> Si un archivo con el mismo nombre ya existe, será <span class="badge bg-danger text-white replaced-badge">REEMPLAZADO</span> automáticamente.</p>
                            </div>

                            <!-- Botón de enviar -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg" id="submitBtn" disabled>
                                    <i class="bi bi-upload"></i> Subir Archivos
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Área de resultados -->
                <div id="result" class="mt-4"></div>
            </div>
        </div>
    </div>

    <!-- Nueva sección para descargar PDFs combinados -->
<div class="row mt-5">
    <div class="col-md-8 offset-md-2">
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-download"></i> Descargar PDFs Combinados</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- GLAMA -->
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="bi bi-folder-fill fs-1 text-success"></i>
                                </div>
                                <h5>Carpeta GLAMA</h5>
                                <p class="text-muted small">Contiene archivos con "gl"</p>
                                
                                <div class="mb-3" id="glamaFileCount">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                    Cargando archivos...
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-success" onclick="downloadCombinedPDF('GLAMA')" id="btnDownloadGLAMA" disabled>
                                        <i class="bi bi-file-pdf"></i> Descargar PDF Combinado
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="viewFolderContents('GLAMA')">
                                        <i class="bi bi-eye"></i> Ver Contenido
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- MORYSAN -->
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="bi bi-folder-fill fs-1 text-warning"></i>
                                </div>
                                <h5>Carpeta MORYSAN</h5>
                                <p class="text-muted small">Contiene archivos con "mor"</p>
                                
                                <div class="mb-3" id="morysanFileCount">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                    Cargando archivos...
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-warning" onclick="downloadCombinedPDF('MORYSAN')" id="btnDownloadMORYSAN" disabled>
                                        <i class="bi bi-file-pdf"></i> Descargar PDF Combinado
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="viewFolderContents('MORYSAN')">
                                        <i class="bi bi-eye"></i> Ver Contenido
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contenido de la carpeta (se muestra al hacer clic en "Ver Contenido") -->
                <div id="folderContents" class="mt-4" style="display: none;">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0" id="folderContentsTitle"></h6>
                            <button type="button" class="btn-close float-end" onclick="hideFolderContents()"></button>
                        </div>
                        <div class="card-body">
                            <div id="folderFilesList"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const fileInput = document.getElementById('fileInput');
        const dropArea = document.getElementById('dropArea');
        const selectedFiles = document.getElementById('selectedFiles');
        const fileList = document.getElementById('fileList');
        const submitBtn = document.getElementById('submitBtn');
        const uploadForm = document.getElementById('uploadForm');
        const resultDiv = document.getElementById('result');

        // Evento para seleccionar archivos
        fileInput.addEventListener('change', handleFiles);

        // Funcionalidad de arrastrar y soltar
        dropArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropArea.classList.add('border-primary', 'bg-light');
        });

        dropArea.addEventListener('dragleave', () => {
            dropArea.classList.remove('border-primary', 'bg-light');
        });

        dropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            dropArea.classList.remove('border-primary', 'bg-light');
            fileInput.files = e.dataTransfer.files;
            handleFiles();
        });

        function handleFiles() {
            selectedFiles.innerHTML = '';
            const files = fileInput.files;
            
            if (files.length > 0) {
                fileList.style.display = 'block';
                submitBtn.disabled = false;
                
                // Limitar a solo PDF
                const pdfFiles = Array.from(files).filter(file => 
                    file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')
                );
                
                // Actualizar el input con solo PDFs
                const dataTransfer = new DataTransfer();
                pdfFiles.forEach(file => dataTransfer.items.add(file));
                fileInput.files = dataTransfer.files;
                
                // Mostrar lista de archivos
                pdfFiles.forEach(file => {
                    const folder = getFolderForFile(file.name);
                    const badgeClass = folder === 'GLAMA' ? 'bg-success' : 'bg-warning text-dark';
                    
                    const fileItem = document.createElement('div');
                    fileItem.className = 'd-flex justify-content-between align-items-center border-bottom py-2';
                    fileItem.innerHTML = `
                        <div>
                            <i class="bi bi-file-earmark-pdf text-danger"></i>
                            <span class="ms-2">${file.name}</span>
                            <span class="folder-badge badge ${badgeClass}">${folder}</span>
                        </div>
                        <div class="text-muted">
                            ${formatFileSize(file.size)}
                        </div>
                    `;
                    selectedFiles.appendChild(fileItem);
                });
                
                // Mostrar advertencia si hay archivos no PDF
                if (pdfFiles.length !== files.length) {
                    const warning = document.createElement('div');
                    warning.className = 'alert alert-warning alert-dismissible fade show mt-2';
                    warning.innerHTML = `
                        <i class="bi bi-exclamation-triangle"></i>
                        Se han descartado ${files.length - pdfFiles.length} archivos que no son PDF.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    selectedFiles.appendChild(warning);
                }
            } else {
                fileList.style.display = 'none';
                submitBtn.disabled = true;
            }
        }

        function getFolderForFile(filename) {
            const lowerName = filename.toLowerCase();
            if (lowerName.includes('gl')) {
                return 'GLAMA';
            } else if (lowerName.includes('mor')) {
                return 'MORYSAN';
            }
            return 'OTROS';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Envío del formulario con AJAX
// Reemplaza la función de envío AJAX con esta versión mejorada
uploadForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = document.getElementById('submitBtn');
    
    // Deshabilitar botón durante la subida
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Subiendo...';
    
    // Limpiar resultados anteriores
    resultDiv.innerHTML = '';
    
    fetch('upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Primero verificar si la respuesta es JSON válido
        const contentType = response.headers.get("content-type");
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        if (contentType && contentType.includes("application/json")) {
            return response.json();
        } else {
            // Si no es JSON, obtener el texto para debug
            return response.text().then(text => {
                console.error("Respuesta no JSON:", text);
                throw new Error("El servidor no devolvió JSON. Respuesta: " + text.substring(0, 200));
            });
        }
    })
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <h5><i class="bi bi-check-circle"></i> Subida completada</h5>
                    <p>${data.message}</p>
                    ${data.details ? `<hr><pre class="mb-0">${data.details}</pre>` : ''}
                </div>
            `;
            
            // Resetear formulario
            fileInput.value = '';
            selectedFiles.innerHTML = '';
            fileList.style.display = 'none';
            submitBtn.innerHTML = '<i class="bi bi-upload"></i> Subir Archivos';
            submitBtn.disabled = true;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <h5><i class="bi bi-exclamation-triangle"></i> Error</h5>
                    <p>${data.message}</p>
                    ${data.details ? `<hr><pre class="mb-0">${data.details}</pre>` : ''}
                </div>
            `;
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-upload"></i> Intentar nuevamente';
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <h5><i class="bi bi-exclamation-triangle"></i> Error de conexión</h5>
                <p>${error.message}</p>
                <hr>
                <p class="small">Para debug:</p>
                <ol class="small">
                    <li>Verifica que upload.php exista en la misma carpeta</li>
                    <li>Verifica que la carpeta 'uploads' exista y tenga permisos de escritura (chmod 755 o 777)</li>
                    <li>Revisa la consola del navegador (F12 > Consola) para más detalles</li>
                </ol>
            </div>
        `;
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-upload"></i> Intentar nuevamente';
    });
});

// Función para verificar archivos en carpetas
function checkFolderFiles() {
    // Verificar GLAMA
    fetch('check_folder.php?folder=GLAMA')
        .then(response => response.json())
        .then(data => {
            const glamaElement = document.getElementById('glamaFileCount');
            const glamaButton = document.getElementById('btnDownloadGLAMA');
            
            if (data.count > 0) {
                glamaElement.innerHTML = `
                    <span class="badge bg-success">${data.count}</span> archivo(s) PDF
                    <br><small class="text-muted">Total: ${formatFileSize(data.totalSize)}</small>
                `;
                glamaButton.disabled = false;
            } else {
                glamaElement.innerHTML = '<span class="badge bg-secondary">0 archivos</span>';
                glamaButton.disabled = true;
            }
        });
    
    // Verificar MORYSAN
    fetch('check_folder.php?folder=MORYSAN')
        .then(response => response.json())
        .then(data => {
            const morysanElement = document.getElementById('morysanFileCount');
            const morysanButton = document.getElementById('btnDownloadMORYSAN');
            
            if (data.count > 0) {
                morysanElement.innerHTML = `
                    <span class="badge bg-warning">${data.count}</span> archivo(s) PDF
                    <br><small class="text-muted">Total: ${formatFileSize(data.totalSize)}</small>
                `;
                morysanButton.disabled = false;
            } else {
                morysanElement.innerHTML = '<span class="badge bg-secondary">0 archivos</span>';
                morysanButton.disabled = true;
            }
        });
}

// Función para descargar PDF combinado
function downloadCombinedPDF(folder) {
    // Mostrar loading
    const button = folder === 'GLAMA' ? 
        document.getElementById('btnDownloadGLAMA') : 
        document.getElementById('btnDownloadMORYSAN');
    
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Combinando...';
    button.disabled = true;
    
    // Descargar el PDF combinado
    window.location.href = 'merge.php?folder=' + folder;
    
    // Restaurar botón después de 3 segundos
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
        
        // Actualizar conteo de archivos
        checkFolderFiles();
    }, 3000);
}

// Función para ver contenido de carpeta
function viewFolderContents(folder) {
    fetch('check_folder.php?folder=' + folder + '&list=true')
        .then(response => response.json())
        .then(data => {
            const contentsDiv = document.getElementById('folderContents');
            const titleDiv = document.getElementById('folderContentsTitle');
            const filesListDiv = document.getElementById('folderFilesList');
            
            titleDiv.textContent = `Archivos en ${folder} (${data.count} archivos)`;
            
            if (data.files && data.files.length > 0) {
                let filesHTML = '<div class="list-group">';
                data.files.forEach(file => {
                    filesHTML += `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-file-earmark-pdf text-danger"></i>
                                <span class="ms-2">${file.name}</span>
                            </div>
                            <div>
                                <span class="badge bg-secondary">${formatFileSize(file.size)}</span>
                                <span class="badge bg-light text-dark ms-1">${new Date(file.modified).toLocaleDateString()}</span>
                            </div>
                        </div>
                    `;
                });
                filesHTML += '</div>';
                filesListDiv.innerHTML = filesHTML;
            } else {
                filesListDiv.innerHTML = '<p class="text-muted text-center">No hay archivos en esta carpeta.</p>';
            }
            
            contentsDiv.style.display = 'block';
            contentsDiv.scrollIntoView({ behavior: 'smooth' });
        });
}

// Función para ocultar contenido de carpeta
function hideFolderContents() {
    document.getElementById('folderContents').style.display = 'none';
}

// Verificar archivos al cargar la página y después de subir archivos
document.addEventListener('DOMContentLoaded', function() {
    checkFolderFiles();
    
    // Actualizar cada 30 segundos
    setInterval(checkFolderFiles, 30000);
});

// Actualizar después de una subida exitosa
// Modifica la parte del éxito en el fetch de subida para llamar a checkFolderFiles()
// Busca en el código donde dice "if (data.success) {" y agrega:
// checkFolderFiles(); // Actualizar lista de archivos
    </script>
</body>
</html>