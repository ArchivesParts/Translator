import java.io.File;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.net.URI;
import java.util.ArrayList;
import java.util.Collections;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Iterator;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.logging.Level;
import java.util.logging.Logger;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import javax.swing.DefaultCellEditor;
import javax.swing.table.TableCellEditor;

/**
 * @author Gregory ANNE <gregonmac@gmail.com>
 */
class Translator {

    private final List<String> extension;
    private final Map<String, Datas> translations;
    private final Pattern translatePattern;
    private final Pattern labelPattern;
    private final Set<String> extractedSources;
    private final Editor editor;

    public Translator(Editor editor) {
        this.editor = editor;
        this.extension = new ArrayList<String>();
        this.extractedSources = new HashSet<String>();
        this.extension.add("php");
        this.extension.add("phtml");

        translatePattern = Pattern.compile("translate\\(\\s*['|\"]([^'|\"]*((?<=\\\\)['|\"][^'|\"]*)*)['|\"]\\s*\\)");
        labelPattern = Pattern.compile("['|\"]label['|\"]\\s*=>\\s*['|\"]([^'|\"]*((?<=\\\\)['|\"]*)*)['|\"]");

        this.translations = new HashMap<String, Datas>();
    }

    public void addTranslationFile(String local, File translationFile) {
        Datas datas = new Datas(local, translationFile);
        translations.put(local, datas);
        editor.jTable1.setModel(datas);
    }

    public void processFolder(File folder) throws FileNotFoundException, IOException {
        List<File> files = getFileToProcess(folder);
        for (Iterator<File> it = files.iterator(); it.hasNext();) {
            File file = it.next();
            processFile(file);
        }
        List<String> orderedList = new ArrayList<String>(extractedSources);
        Collections.sort(orderedList);

        for (Map.Entry<String, Datas> entry : translations.entrySet()) {
            String string = entry.getKey();
            Datas datas = entry.getValue();
            datas.removeMissingTranslations(orderedList);
            datas.addTranslations(orderedList);
        }
    }

    private List<File> getFileToProcess(File folder) {
        ArrayList<File> result = new ArrayList<File>();

        File[] list = folder.listFiles();
        if (list != null) {
            for (int i = 0; i < list.length; i++) {
                if (list[i].isFile()) {
                    if (extension.contains(list[i].getName().substring(list[i].getName().lastIndexOf('.') + 1))) {
                        result.add(list[i]);
                    }
                } else {
                    result.addAll(this.getFileToProcess(list[i]));
                }
            }
        }
        return result;
    }

    public void processFile(File file) throws FileNotFoundException, IOException {
        URI uri = file.toURI();
        byte[] bytes = null;
        try {
            bytes = java.nio.file.Files.readAllBytes(java.nio.file.Paths.get(uri));
        } catch (IOException e) {
            Logger.getLogger(Translator.class.getName()).log(Level.SEVERE, null, e);
        }

        String content = new String(bytes);
        Matcher matcher = translatePattern.matcher(content);
        while (matcher.find()) {
            extractedSources.add(matcher.group(1));
        }
        matcher = labelPattern.matcher(content);
        while (matcher.find()) {
            extractedSources.add(matcher.group(1));
        }

    }
}
